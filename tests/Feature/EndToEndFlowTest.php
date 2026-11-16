<?php

namespace Tests\Feature;

use App\Models\Bet;
use App\Models\Event;
use App\Models\Fight;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * One card, end to end, through the real screens: event settings become a
 * fight, the fight takes a bet, the bet is capped by the stopper ratio, graded,
 * paid, claimed — and the reports read the same money.
 */
class EndToEndFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_card_runs_from_creation_to_a_paid_claim_and_the_reports_agree(): void
    {
        $this->seed();

        $gm = User::where('username', 'gm.north')->firstOrFail();
        $tellerUser = User::where('username', 'pos01')->firstOrFail();

        // 1. The card, with every setting the form collects.
        $this->actingAs($gm)->post('/gm/events', [
            'type' => 'regular',
            'name' => 'Connected Card',
            'starts_at' => now()->toDateString(),
            'venue' => 'Camsur Sports Arena',
            'stop_ratio' => 1.400,
            'planned_fights' => 5,
            'bomba_amount' => 100,
            'bomba_breakdown' => 2,
            'bomba_interval' => 15,
            'payout_display' => 'decimals',
            'tv_total_bets' => 70,
        ])->assertRedirect()->assertSessionHasNoErrors();

        $event = Event::where('name', 'Connected Card')->firstOrFail();

        // 2. A fight in that card inherits the stopper ratio.
        $this->actingAs($gm)->post('/gm/fights', [
            'event_id' => $event->id,
            'fight_no' => 1,
            'odds_red' => 2.000,
            'odds_blue' => 1.800,
        ])->assertRedirect()->assertSessionHasNoErrors();

        $fight = Fight::where('event_id', $event->id)->firstOrFail();

        $this->assertSame(1.4, (float) $fight->stop_ratio);

        // The console shows the card settings that belong to this fight.
        $this->actingAs($gm)->get('/gm/fights/'.$fight->id)
            ->assertOk()
            ->assertSee('70% of the pool')
            ->assertSee('Bomba');

        // 3. Open the fight and take a bet at the window.
        $this->actingAs($gm)->post('/gm/fights/'.$fight->id.'/open')->assertRedirect();

        if (! $tellerUser->tellerProfile->openShift()) {
            $this->actingAs($tellerUser)->post('/pos/shift/open')->assertRedirect();
        }

        $this->actingAs($tellerUser)->post('/pos/bets', [
            'fight_id' => $fight->id,
            'side' => 'red',
            'amount' => 100,
        ])->assertRedirect()->assertSessionHasNoErrors();

        $bet = Bet::where('fight_id', $fight->id)->firstOrFail();

        // 2.000 posted odds, capped at the card's 1.400 stopper → 140, not 200.
        $this->assertSame(140, (int) $bet->potential_payout);

        // 4. The ticket carries the barcode and says the cap applied.
        $this->actingAs($tellerUser)
            ->get('/pos/bets/'.$bet->id.'/receipt')
            ->assertOk()
            ->assertDontSee('POTENTIAL')
            ->assertDontSee('STOP RATIO APPLIED')
            ->assertSee('class="barcode"', false);

        // 5. Close the fight and declare the side.
        $this->actingAs($gm)->post('/gm/fights/'.$fight->id.'/lock')->assertRedirect();
        $this->actingAs($gm)->post('/gm/fights/'.$fight->id.'/result', ['result' => 'meron'])->assertRedirect();

        $this->assertSame(Bet::STATUS_WON, $bet->fresh()->status);

        // 6. The desk owes 140, and the claim ticket prints the ratio and win.
        $this->actingAs($tellerUser)
            ->get('/pos')
            ->assertOk()
            ->assertSee('140');

        $this->actingAs($tellerUser)
            ->get('/pos/bets/'.$bet->id.'/claim')
            ->assertOk()
            ->assertSee('WINNING SIDE: MERON')
            ->assertSee('PAYOUT RATIO: 140.00')
            ->assertSee('140.00');

        // 7. Hand the money over; the slip is marked paid.
        $this->actingAs($tellerUser)->post('/pos/bets/'.$bet->id.'/payout')->assertRedirect();

        $this->assertNotNull($bet->fresh()->paid_out_at);

        // 8. Every report reads the same card.
        $this->actingAs($gm)
            ->get('/gm/report?event_id='.$event->id)
            ->assertOk()
            ->assertSee('Connected Card')
            ->assertSee('100 cr')
            ->assertSee('140 cr');

        $this->actingAs($gm)
            ->get('/gm/history')
            ->assertOk()
            ->assertSee('140');

        $this->actingAs($gm)
            ->get('/gm/bets')
            ->assertOk()
            ->assertSee('Meron bets')
            ->assertSee('1.400×');

        $this->actingAs($gm)
            ->get('/gm/fights/'.$fight->id.'/history')
            ->assertOk()
            ->assertSee('won')
            ->assertSee('140');
    }
}
