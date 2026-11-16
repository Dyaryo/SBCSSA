<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Bet;
use App\Models\ShiftSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SupervisorShiftDetailTest extends TestCase
{
    use RefreshDatabase;

    private function supervisor(): User
    {
        return User::where('username', 'supervisor')->firstOrFail();
    }

    private function openShiftWithUnsettledBet(): ShiftSession
    {
        return ShiftSession::where('status', ShiftSession::STATUS_OPEN)
            ->whereHas('bets', fn ($q) => $q->where('status', Bet::STATUS_ACTIVE))
            ->firstOrFail();
    }

    public function test_the_shift_detail_lists_the_unsettled_bets_and_what_they_owe(): void
    {
        $this->seed();

        $shift = $this->openShiftWithUnsettledBet();
        $bets = $shift->bets()->where('status', Bet::STATUS_ACTIVE)->get();

        $this->actingAs($this->supervisor())
            ->get('/supervisor/shifts/'.$shift->id)
            ->assertOk()
            ->assertSee('Unsettled bets')
            ->assertSee('data-unsettled-bet="'.$bets->first()->id.'"', false)
            ->assertSee(number_format((int) $bets->sum('amount_credits')));
    }

    public function test_a_forced_close_does_not_hide_the_money_owed(): void
    {
        $this->seed();

        $shift = $this->openShiftWithUnsettledBet();
        $bet = $shift->bets()->where('status', Bet::STATUS_ACTIVE)->firstOrFail();

        $this->actingAs($this->supervisor())
            ->post('/supervisor/shifts/'.$shift->id.'/close', ['reason' => 'teller left the booth'])
            ->assertRedirect();

        $this->actingAs($this->supervisor())
            ->get('/supervisor/shifts/'.$shift->id)
            ->assertOk()
            ->assertSee('the money is still owed')
            ->assertSee('data-unsettled-bet="'.$bet->id.'"', false)
            ->assertSee('force_closed');
    }

    public function test_the_close_button_names_the_fights_that_need_grading(): void
    {
        $this->seed();

        $shift = $this->openShiftWithUnsettledBet();
        $fight = $shift->bets()->where('status', Bet::STATUS_ACTIVE)->firstOrFail()->fight;

        $this->actingAs($this->supervisor())
            ->get('/supervisor/shifts')
            ->assertOk()
            ->assertSee('still need grading')
            ->assertSee('#'.$fight->fight_no.' Meron vs Wala')
            ->assertSee('Needs grading:');
    }

    public function test_a_force_close_records_the_unsettled_bets_it_leaves_behind(): void
    {
        $this->seed();

        $shift = $this->openShiftWithUnsettledBet();
        $unsettled = $shift->bets()->where('status', Bet::STATUS_ACTIVE)->get();

        $this->actingAs($this->supervisor())
            ->post('/supervisor/shifts/'.$shift->id.'/close', ['reason' => 'terminal died'])
            ->assertRedirect();

        $log = AuditLog::where('action', 'shift.force_closed')->where('entity_id', $shift->id)->firstOrFail();
        $payload = (string) $log->getRawOriginal('after');

        $this->assertStringContainsString('"unsettled_bets":'.$unsettled->count(), $payload);
        $this->assertStringContainsString('"unsettled_stake":'.(int) $unsettled->sum('amount_credits'), $payload);
    }

    public function test_a_shift_without_unsettled_bets_still_gets_a_plain_confirmation(): void
    {
        $this->seed();

        // Settle every active bet so the seeded open shift owes nothing.
        Bet::where('status', Bet::STATUS_ACTIVE)->update(['status' => Bet::STATUS_REFUNDED, 'settled_at' => now()]);

        $this->actingAs($this->supervisor())
            ->get('/supervisor/shifts')
            ->assertOk()
            ->assertSee('Its totals are sealed into a report snapshot')
            ->assertDontSee('still need grading');
    }

    public function test_the_shift_detail_names_the_fights_still_to_grade(): void
    {
        $this->seed();

        $shift = $this->openShiftWithUnsettledBet();
        $fight = $shift->bets()->where('status', Bet::STATUS_ACTIVE)->firstOrFail()->fight;

        $this->actingAs($this->supervisor())
            ->get('/supervisor/shifts/'.$shift->id)
            ->assertOk()
            ->assertSee('Fights still to grade')
            ->assertSee('data-pending-fight="'.$fight->fight_no.'"', false);
    }

    public function test_the_shifts_index_warns_about_money_still_riding_on_open_tills(): void
    {
        $this->seed();

        $shift = $this->openShiftWithUnsettledBet();
        $stake = (int) $shift->bets()->where('status', Bet::STATUS_ACTIVE)->sum('amount_credits');

        $this->actingAs($this->supervisor())
            ->get('/supervisor/shifts')
            ->assertOk()
            ->assertSee('unsettled bet(s) across open tills')
            ->assertSee(number_format($stake));
    }

    public function test_a_settled_shift_reports_no_unsettled_bets(): void
    {
        $this->seed();

        $shift = ShiftSession::where('status', ShiftSession::STATUS_OPEN)
            ->whereDoesntHave('bets', fn ($q) => $q->where('status', Bet::STATUS_ACTIVE))
            ->first();

        if (! $shift) {
            $this->markTestSkipped('Every seeded open shift has an unsettled bet.');
        }

        $this->actingAs($this->supervisor())
            ->get('/supervisor/shifts/'.$shift->id)
            ->assertOk()
            ->assertSee('Nothing unsettled on this till');
    }

    public function test_only_the_supervisor_can_open_the_shift_detail(): void
    {
        $this->seed();

        $shift = ShiftSession::firstOrFail();

        $this->actingAs(User::where('username', 'gm.north')->firstOrFail())
            ->get('/supervisor/shifts/'.$shift->id)
            ->assertForbidden();

        $this->actingAs(User::where('username', 'pos01')->firstOrFail())
            ->get('/supervisor/shifts/'.$shift->id)
            ->assertForbidden();
    }
}
