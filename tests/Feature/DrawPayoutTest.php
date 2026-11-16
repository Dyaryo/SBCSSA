<?php

namespace Tests\Feature;

use App\Models\Bet;
use App\Models\Event;
use App\Models\Fight;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Draw is a priced side on the card (x7 on the paper board), not a refund.
 */
class DrawPayoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_card_takes_a_draw_ratio_and_the_board_prices_it(): void
    {
        $this->seed();

        $gm = User::where('username', 'gm.north')->firstOrFail();

        $this->actingAs($gm)->post('/gm/events', [
            'type' => 'regular',
            'name' => 'Draw Card',
            'starts_at' => now()->toDateString(),
            'draw_ratio' => 7,
            'tv_total_bets' => 100,
            'payout_display' => 'decimals',
        ])->assertRedirect()->assertSessionHasNoErrors();

        $event = Event::where('name', 'Draw Card')->firstOrFail();

        $this->assertSame(7.0, (float) $event->draw_ratio);

        $this->actingAs($gm)->post('/gm/fights', [
            'event_id' => $event->id,
            'fight_no' => 1,
            'odds_red' => 2.000,
            'odds_blue' => 1.800,
        ])->assertRedirect()->assertSessionHasNoErrors();

        $fight = Fight::where('event_id', $event->id)->firstOrFail();

        $this->assertSame(7.0, $fight->drawRatio());

        // The board prints the draw price the way the arena board does: 1–7.
        $this->get('/board?event_id='.$event->id)
            ->assertOk()
            ->assertSee('1–7');
    }

    public function test_a_draw_ticket_pays_the_card_ratio_and_the_window_can_print_it(): void
    {
        $this->seed();

        $gm = User::where('username', 'gm.north')->firstOrFail();
        $teller = User::where('username', 'pos01')->firstOrFail();

        $event = Event::create([
            'code' => 'REG-DRAW',
            'name' => 'Draw Night',
            'type' => Event::TYPE_REGULAR,
            'game_master_id' => $gm->gameMasterProfile->id,
            'starts_at' => now(),
            'status' => Event::STATUS_OPEN,
            'draw_ratio' => 5.5,
        ]);

        $fight = Fight::create([
            'event_id' => $event->id,
            'fight_no' => 1,
            'odds_red' => 1.850,
            'odds_blue' => 1.850,
            'status' => Fight::STATUS_SCHEDULED,
        ]);

        $this->actingAs($gm)->post('/gm/fights/'.$fight->id.'/open')->assertRedirect();

        if (! $teller->tellerProfile->openShift()) {
            $this->actingAs($teller)->post('/pos/shift/open')->assertRedirect();
        }

        $this->actingAs($teller)->post('/pos/bets', [
            'fight_id' => $fight->id,
            'side' => 'draw',
            'amount' => 100,
        ])->assertRedirect()->assertSessionHasNoErrors();

        $bet = Bet::where('fight_id', $fight->id)->firstOrFail();

        $this->assertSame(Fight::SIDE_DRAW, $bet->side);
        $this->assertSame(550, (int) $bet->potential_payout);
        $this->assertSame(5.5, (float) $bet->odds_at_placement);

        // The window shows the draw price before the cashier picks a side.
        $this->actingAs($teller)
            ->get('/pos')
            ->assertOk()
            ->assertSee('DRAW')
            ->assertSee('5.500×');

        // Ticket and claim slip read DRAW, not a corner.
        $this->actingAs($teller)
            ->get('/pos/bets/'.$bet->id.'/receipt')
            ->assertOk()
            ->assertSee('BET TYPE: DRAW');

        $this->actingAs($gm)->post('/gm/fights/'.$fight->id.'/lock')->assertRedirect();
        $this->actingAs($gm)->post('/gm/fights/'.$fight->id.'/result', ['result' => 'draw'])->assertRedirect();

        $this->assertSame(Bet::STATUS_WON, $bet->fresh()->status);

        $this->actingAs($teller)
            ->get('/pos/bets/'.$bet->id.'/claim')
            ->assertOk()
            ->assertSee('WINNING SIDE: DRAW')
            ->assertSee('PAYOUT RATIO: 550.00')
            ->assertSee('550.00');
    }
}
