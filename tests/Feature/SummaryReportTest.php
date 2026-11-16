<?php

namespace Tests\Feature;

use App\Models\Bet;
use App\Models\Event;
use App\Models\Fight;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SummaryReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_summary_report_totals_the_game_masters_book(): void
    {
        $this->seed();
        $gm = User::where('username', 'gm.north')->firstOrFail();

        $staked = (int) Bet::whereHas('fight.event', fn ($q) => $q->where('game_master_id', $gm->gameMasterProfile->id))
            ->sum('amount_credits');
        $this->assertGreaterThan(0, $staked);

        $this->actingAs($gm)
            ->get('/gm/report')
            ->assertOk()
            ->assertSee('Overall summary report')
            ->assertSee('Per event')
            ->assertSee(number_format($staked));
    }

    public function test_the_report_only_shows_the_game_masters_own_events(): void
    {
        $this->seed();
        $south = User::where('username', 'gm.south')->firstOrFail();
        $north = User::where('username', 'gm.north')->firstOrFail();

        $northEvent = Event::where('game_master_id', $north->gameMasterProfile->id)->firstOrFail();

        $this->actingAs($south)
            ->get('/gm/report')
            ->assertOk()
            ->assertDontSee($northEvent->code);
    }

    public function test_the_report_can_be_scoped_to_one_event(): void
    {
        $this->seed();
        $gm = User::where('username', 'gm.north')->firstOrFail();

        $event = Event::where('game_master_id', $gm->gameMasterProfile->id)
            ->whereHas('fights.bets')
            ->firstOrFail();

        $staked = (int) Bet::whereHas('fight', fn ($q) => $q->where('event_id', $event->id))->sum('amount_credits');

        $this->actingAs($gm)
            ->get('/gm/report?event_id='.$event->id)
            ->assertOk()
            ->assertSee($event->name)
            ->assertSee('This event')
            ->assertSee(number_format($staked));

        $other = Event::where('game_master_id', '!=', $gm->gameMasterProfile->id)->first();

        if ($other) {
            $this->actingAs($gm)
                ->get('/gm/report?event_id='.$other->id)
                ->assertNotFound();
        }
    }

    public function test_the_overall_sheet_shows_the_commission_split(): void
    {
        $this->seed();

        $this->actingAs(User::where('username', 'gm.north')->firstOrFail())
            ->get('/gm/report')
            ->assertOk()
            ->assertSee('5.0% gross comm.')
            ->assertSee('Net comm')
            ->assertSee('Operator 80% sharing')
            ->assertSee('CBS 20% sharing')
            ->assertSee('Total unclaimed / unpaid')
            ->assertSee('Draw bets + draw win')
            ->assertSee("Teller's short / over");
    }

    public function test_expenses_reduce_the_net_commission(): void
    {
        $this->seed();
        $gm = User::where('username', 'gm.north')->firstOrFail();

        $staked = (int) Bet::whereHas('fight.event', fn ($q) => $q->where('game_master_id', $gm->gameMasterProfile->id))
            ->sum('amount_credits');
        $net = max(0, (int) round($staked * 0.05) - 500);

        $this->actingAs($gm)
            ->get('/gm/report?expenses=500&cash_on_hand=0')
            ->assertOk()
            ->assertSee(number_format($net).' cr');
    }

    public function test_the_fight_book_lists_both_sides_and_draw_values(): void
    {
        $this->seed();

        $this->actingAs(User::where('username', 'gm.north')->firstOrFail())
            ->get('/gm/history')
            ->assertOk()
            ->assertSee('Fight book')
            ->assertSee('Meron value')
            ->assertSee('Meron payout')
            ->assertSee('Wala value')
            ->assertSee('Draw value')
            ->assertSee('Draw payout');
    }

    public function test_the_bet_history_shows_the_side_totals_and_paper_columns(): void
    {
        $this->seed();

        $this->actingAs(User::where('username', 'gm.north')->firstOrFail())
            ->get('/gm/bets')
            ->assertOk()
            ->assertSee('Meron bets')
            ->assertSee('Wala bets')
            ->assertSee('Draw bets')
            ->assertSee('Total bets')
            ->assertSee('Account no.')
            ->assertSee('Payout ratio');
    }

    public function test_a_fight_history_lists_every_slip_with_tellers_and_results(): void
    {
        $this->seed();
        $gm = User::where('username', 'gm.north')->firstOrFail();

        $fight = Fight::whereHas('event', fn ($q) => $q->where('game_master_id', $gm->gameMasterProfile->id))
            ->whereHas('bets')
            ->orderBy('id')
            ->firstOrFail();

        $this->actingAs($gm)
            ->get('/gm/fights/'.$fight->id.'/history')
            ->assertOk()
            ->assertSee('Fight #'.$fight->fight_no.' bet history')
            ->assertSee('Every slip on this fight')
            ->assertSee('On meron')
            ->assertSee('On wala');
    }

    public function test_another_game_master_cannot_open_the_history(): void
    {
        $this->seed();
        $north = User::where('username', 'gm.north')->firstOrFail();
        $south = User::where('username', 'gm.south')->firstOrFail();

        $fight = Fight::whereHas('event', fn ($q) => $q->where('game_master_id', $north->gameMasterProfile->id))
            ->firstOrFail();

        $this->actingAs($south)
            ->get('/gm/fights/'.$fight->id.'/history')
            ->assertForbidden();
    }
}
