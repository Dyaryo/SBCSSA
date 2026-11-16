<?php

namespace Tests\Feature;

use App\Models\Bet;
use App\Models\Event;
use App\Models\Fight;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TellerCancellationTest extends TestCase
{
    use RefreshDatabase;

    private function openFight(): Fight
    {
        $this->seed();

        $gm = User::where('username', 'gm.north')->firstOrFail();

        $event = Event::create([
            'code' => 'REG-CANCEL',
            'name' => 'Cancellation Card',
            'type' => Event::TYPE_REGULAR,
            'game_master_id' => $gm->gameMasterProfile->id,
            'starts_at' => now(),
            'status' => Event::STATUS_OPEN,
        ]);

        return Fight::create([
            'event_id' => $event->id,
            'fight_no' => 1,
            'odds_red' => 1.850,
            'odds_blue' => 2.050,
            'status' => Fight::STATUS_BETTING_OPEN,
        ]);
    }

    private function placeBet(User $teller, Fight $fight, int $amount = 100): Bet
    {
        if (! $teller->tellerProfile->openShift()) {
            $this->actingAs($teller)->post('/pos/shift/open')->assertRedirect();
        }

        $this->actingAs($teller)
            ->post('/pos/bets', ['fight_id' => $fight->id, 'side' => 'red', 'amount' => $amount])
            ->assertRedirect();

        return Bet::where('fight_id', $fight->id)->latest('id')->firstOrFail();
    }

    public function test_a_teller_can_cancel_their_own_bet_and_the_supervisor_sees_it(): void
    {
        $fight = $this->openFight();
        $teller = User::where('username', 'pos01')->firstOrFail();
        $bet = $this->placeBet($teller, $fight);

        $before = (int) $teller->walletOrCreate()->balance_credits;

        $this->actingAs($teller)
            ->get('/pos')
            ->assertOk()
            ->assertSee(route('pos.bets.cancel.form', $bet), false);

        $this->actingAs($teller)
            ->get('/pos/bets/'.$bet->id.'/cancel')
            ->assertOk()
            ->assertSee('Cancel and refund this bet');

        $this->actingAs($teller)
            ->post('/pos/bets/'.$bet->id.'/cancel', ['reason' => 'wrong side entered'])
            ->assertRedirect(route('pos.dashboard'))
            ->assertSessionHas('status')
            ->assertSessionHas('print_cancellation', route('pos.bets.cancellation', $bet));

        $bet->refresh();

        $this->assertSame(Bet::STATUS_VOID, $bet->status);
        $this->assertSame($teller->id, (int) $bet->voided_by);
        $this->assertSame('wrong side entered', $bet->void_reason);
        $this->assertSame($before + 100, (int) $teller->walletOrCreate()->fresh()->balance_credits);
        $this->assertDatabaseHas('audit_logs', ['action' => 'bet.voided', 'entity_id' => $bet->id]);

        $this->actingAs($teller)
            ->get('/pos')
            ->assertOk()
            ->assertSee('data-auto-print', false)
            ->assertSee(route('pos.bets.cancellation', $bet), false);

        $this->actingAs($teller)
            ->get('/pos/bets/'.$bet->id.'/cancellation')
            ->assertOk()
            ->assertSee('CANCELLED')
            ->assertSee('REFUNDED: 100.00')
            ->assertSee('wrong side entered')
            ->assertSee('class="barcode"', false);

        $this->actingAs($teller)
            ->get('/pos/history')
            ->assertOk()
            ->assertSee('Reprint cancellation')
            ->assertSee(route('pos.bets.cancellation', ['bet' => $bet, 'auto' => 1]), false);

        $this->actingAs(User::where('username', 'supervisor')->firstOrFail())
            ->get('/supervisor')
            ->assertOk()
            ->assertSee('Teller cancellations')
            ->assertSee('wrong side entered')
            ->assertSee('POS 001');
    }

    public function test_a_teller_cannot_cancel_another_terminals_bet(): void
    {
        $fight = $this->openFight();
        $owner = User::where('username', 'pos01')->firstOrFail();
        $other = User::where('username', 'pos02')->firstOrFail();
        $bet = $this->placeBet($owner, $fight);

        $this->actingAs($other)
            ->post('/pos/bets/'.$bet->id.'/cancel', ['reason' => 'not mine'])
            ->assertForbidden();

        $this->assertSame(Bet::STATUS_ACTIVE, $bet->fresh()->status);
    }

    public function test_cancellation_is_refused_once_the_fight_is_locked(): void
    {
        $fight = $this->openFight();
        $teller = User::where('username', 'pos01')->firstOrFail();
        $bet = $this->placeBet($teller, $fight);

        $fight->update(['status' => Fight::STATUS_BETTING_LOCKED]);

        $this->actingAs($teller)
            ->post('/pos/bets/'.$bet->id.'/cancel', ['reason' => 'too late'])
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertSame(Bet::STATUS_ACTIVE, $bet->fresh()->status);

        $this->actingAs($teller)
            ->get('/pos/bets/'.$bet->id.'/cancel')
            ->assertRedirect(route('pos.dashboard'))
            ->assertSessionHas('error');

        $this->actingAs($teller)
            ->get('/pos/bets/'.$bet->id.'/cancellation')
            ->assertRedirect(route('pos.dashboard'))
            ->assertSessionHas('error');
    }
}
