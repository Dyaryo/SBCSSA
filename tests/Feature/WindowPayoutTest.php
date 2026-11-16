<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Bet;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The window side of the system: the Game Master grades fights, the teller pays
 * out the graded winning slips exactly once.
 */
class WindowPayoutTest extends TestCase
{
    use RefreshDatabase;

    private function wonBetFor(string $tellerCode): Bet
    {
        return Bet::whereHas('teller', fn ($q) => $q->where('teller_code', $tellerCode))
            ->where('status', Bet::STATUS_WON)
            ->firstOrFail();
    }

    public function test_a_winning_slip_is_marked_paid_once(): void
    {
        $this->seed();

        $user = User::where('username', 'pos01')->firstOrFail();
        $bet = $this->wonBetFor('POS 001');

        $this->assertNull($bet->paid_out_at);

        $this->actingAs($user)->post('/pos/bets/'.$bet->id.'/payout')
            ->assertRedirect()
            ->assertSessionHas('print_claim', route('pos.bets.claim', $bet));

        $this->assertNotNull($bet->fresh()->paid_out_at);
        $this->assertSame($user->id, (int) $bet->fresh()->paid_out_by);

        // A second attempt must not create a second payout record.
        $this->actingAs($user)->post('/pos/bets/'.$bet->id.'/payout')->assertRedirect();

        $this->assertSame(1, AuditLog::where('action', 'bet.paid_out')->where('entity_id', $bet->id)->count());
    }

    public function test_only_a_winning_slip_can_be_paid_out(): void
    {
        $this->seed();

        $user = User::where('username', 'pos01')->firstOrFail();

        $bet = Bet::whereHas('teller', fn ($q) => $q->where('teller_code', 'POS 001'))
            ->where('status', '!=', Bet::STATUS_WON)
            ->firstOrFail();

        $this->actingAs($user)->post('/pos/bets/'.$bet->id.'/payout')->assertRedirect();

        $this->assertNull($bet->fresh()->paid_out_at);
        $this->assertSame(0, AuditLog::where('action', 'bet.paid_out')->count());
    }

    public function test_another_terminal_cannot_record_the_payout(): void
    {
        $this->seed();

        $user = User::where('username', 'pos01')->firstOrFail();

        $foreign = Bet::whereHas('teller', fn ($q) => $q->where('teller_code', '!=', 'POS 001'))->firstOrFail();

        $this->actingAs($user)->post('/pos/bets/'.$foreign->id.'/payout')->assertForbidden();
        $this->assertFalse($foreign->fresh()->isPaidOut());
    }

    public function test_the_desk_lists_the_payouts_still_to_hand_over(): void
    {
        $this->seed();

        $user = User::where('username', 'pos01')->firstOrFail();
        $bet = $this->wonBetFor('POS 001');

        $this->actingAs($user)
            ->get('/pos')
            ->assertOk()
            ->assertSee('Pay out — winning slips to hand over')
            ->assertSee(number_format($bet->potential_payout));
    }

    public function test_a_paid_slip_drops_off_the_payout_queue(): void
    {
        $this->seed();

        $user = User::where('username', 'pos01')->firstOrFail();

        $unpaid = Bet::whereHas('teller', fn ($q) => $q->where('teller_code', 'POS 001'))
            ->where('status', Bet::STATUS_WON)
            ->whereNull('paid_out_at')
            ->pluck('id');

        $this->assertGreaterThan(0, $unpaid->count(), 'The seeded shift should owe at least one payout.');

        foreach ($unpaid as $id) {
            $this->actingAs($user)->post('/pos/bets/'.$id.'/payout');
        }

        $this->actingAs($user)
            ->get('/pos')
            ->assertOk()
            ->assertSee('Nothing to pay out.');
    }

    public function test_the_game_master_dashboard_is_fight_focused(): void
    {
        $this->seed();

        $gm = User::where('username', 'gm.north')->firstOrFail();

        $this->actingAs($gm)
            ->get('/gm')
            ->assertOk()
            ->assertSee('Fights needing you')
            ->assertSee('POS oversight');
    }
}
