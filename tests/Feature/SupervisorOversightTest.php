<?php

namespace Tests\Feature;

use App\Models\Bet;
use App\Models\Event;
use App\Models\Fight;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Whatever the Game Master sets on a card and the tellers do at the window has
 * to be visible to the Supervisor without asking either of them.
 */
class SupervisorOversightTest extends TestCase
{
    use RefreshDatabase;

    private function card(): Event
    {
        $this->seed();

        $gm = User::where('username', 'gm.north')->firstOrFail();

        $this->actingAs($gm)->post('/gm/events', [
            'type' => 'regular',
            'name' => 'Supervisor Card',
            'starts_at' => now()->toDateString(),
            'venue' => 'Camsur Sports Arena',
            'stop_ratio' => 1.400,
            'draw_ratio' => 7,
            'planned_fights' => 12,
            'bomba_amount' => 250,
            'bomba_breakdown' => 2,
            'bomba_interval' => 45,
            'payout_display' => 'whole',
            'tv_total_bets' => 70,
        ])->assertRedirect()->assertSessionHasNoErrors();

        return Event::where('name', 'Supervisor Card')->firstOrFail();
    }

    public function test_the_overview_shows_every_card_with_the_event_form_settings(): void
    {
        $this->card();
        $supervisor = User::where('username', 'supervisor')->firstOrFail();

        $this->actingAs($supervisor)
            ->get('/supervisor')
            ->assertOk()
            ->assertSee('Cards in play')
            ->assertSee('Supervisor Card')
            ->assertSee('1.400×')
            ->assertSee('7.000×')
            ->assertSee('70%')
            ->assertSee('Whole numbers')
            ->assertSee('250 · break 2 · 45s');
    }

    public function test_the_event_report_carries_the_card_settings_and_draw_money(): void
    {
        $event = $this->card();
        $supervisor = User::where('username', 'supervisor')->firstOrFail();

        $this->actingAs($supervisor)
            ->get('/supervisor/reports?scope=event&event_id='.$event->id)
            ->assertOk()
            ->assertSee('Card settings')
            ->assertSee('Planned Fights')
            ->assertSee('Bet Stopper Ratio')
            ->assertSee('Draw Payout Ratio')
            ->assertSee('TV Total Bets')
            ->assertSee('Bomba')
            ->assertSee('7.000×')
            ->assertSee('70%');
    }

    public function test_the_game_master_daily_report_lists_the_cards_and_their_prices(): void
    {
        $event = $this->card();
        $supervisor = User::where('username', 'supervisor')->firstOrFail();

        $this->actingAs($supervisor)
            ->get('/supervisor/reports?scope=gm_daily&game_master_id='.$event->game_master_id.'&date='.now()->toDateString())
            ->assertOk()
            ->assertSee('Cards by this Game Master')
            ->assertSee('Supervisor Card')
            ->assertSee('Tv Share')
            ->assertSee('Draw Ratio');
    }

    public function test_a_draw_bet_shows_as_draw_on_the_supervisor_bets_feed(): void
    {
        $this->seed();

        $supervisor = User::where('username', 'supervisor')->firstOrFail();
        $bet = Bet::whereHas('teller', fn ($q) => $q->where('teller_code', 'POS 001'))->firstOrFail();

        $bet->update(['side' => Fight::SIDE_DRAW, 'odds_at_placement' => 7, 'potential_payout' => (int) $bet->amount_credits * 7]);

        $this->actingAs($supervisor)
            ->get('/supervisor')
            ->assertOk()
            ->assertSee('badge draw', false)
            ->assertSee('draw');
    }
}
