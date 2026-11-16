<?php

namespace Tests\Feature;

use App\Models\Bet;
use App\Models\Fight;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TicketAndTerminalTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_bet_ticket_carries_a_barcode_and_the_cashier_details(): void
    {
        $this->seed();

        $user = User::where('username', 'pos01')->firstOrFail();
        $bet = Bet::whereHas('teller', fn ($q) => $q->where('teller_code', 'POS 001'))->firstOrFail();

        $html = $this->actingAs($user)
            ->get('/pos/bets/'.$bet->id.'/receipt')
            ->assertOk()
            ->assertSee('BET TYPE: '.strtoupper(Fight::sideLabel($bet->side)))
            ->assertSee($bet->teller->teller_code)
            ->getContent();

        $this->assertMatchesRegularExpression('/<svg class="barcode".*?<\/svg>/s', $html, 'The ticket must carry a barcode.');
        $this->assertGreaterThan(60, substr_count($html, '<rect x='), 'The barcode should be made of real bars.');
    }

    public function test_a_winning_slip_prints_a_claim_ticket(): void
    {
        $this->seed();

        $user = User::where('username', 'pos01')->firstOrFail();
        $bet = Bet::whereHas('teller', fn ($q) => $q->where('teller_code', 'POS 001'))
            ->where('status', Bet::STATUS_WON)
            ->firstOrFail();

        $this->actingAs($user)
            ->get('/pos/bets/'.$bet->id.'/claim')
            ->assertOk()
            ->assertSee('CLAIMED')
            ->assertSee('WINNING SIDE: '.strtoupper(Fight::sideLabel($bet->side)))
            ->assertSee('PAYOUT RATIO')
            ->assertSee(number_format($bet->potential_payout).'.00')
            ->assertSee('class="barcode"', false);
    }

    public function test_a_slip_that_did_not_win_cannot_be_claimed(): void
    {
        $this->seed();

        $user = User::where('username', 'pos01')->firstOrFail();
        $bet = Bet::whereHas('teller', fn ($q) => $q->where('teller_code', 'POS 001'))->firstOrFail();

        $bet->update(['status' => Bet::STATUS_ACTIVE]);

        $this->actingAs($user)
            ->from('/pos')
            ->get('/pos/bets/'.$bet->id.'/claim')
            ->assertRedirect('/pos')
            ->assertSessionHas('error');
    }

    public function test_another_terminal_cannot_print_the_claim_ticket(): void
    {
        $this->seed();

        $foreign = Bet::whereHas('teller', fn ($q) => $q->where('teller_code', 'POS 002'))->firstOrFail();

        $this->actingAs(User::where('username', 'pos01')->firstOrFail())
            ->get('/pos/bets/'.$foreign->id.'/claim')
            ->assertForbidden();
    }

    public function test_the_teller_desk_is_a_terminal_with_a_quick_amount_pad(): void
    {
        $this->seed();

        $this->actingAs(User::where('username', 'pos01')->firstOrFail())
            ->get('/pos')
            ->assertOk()
            ->assertSee('pos-terminal', false)
            ->assertSee('Quick amount', false)
            ->assertSee('TOTAL BETS', false)
            ->assertSee('Recent tickets')
            ->assertSee('MERON')
            ->assertSee('WALA')
            ->assertSee('TAB')
            ->assertSee('F2')
            ->assertSee('F3')
            ->assertSee('F4')
            ->assertSee('pos-keys', false);
    }

    public function test_the_grading_screen_offers_the_four_results_and_the_fight_values(): void
    {
        $this->seed();
        $gm = User::where('username', 'gm.north')->firstOrFail();

        $fight = Fight::whereHas('event', fn ($q) => $q->where('game_master_id', $gm->gameMasterProfile->id))
            ->firstOrFail();
        $fight->update(['status' => Fight::STATUS_BETTING_LOCKED]);

        $this->actingAs($gm)
            ->get('/gm/fights/'.$fight->id)
            ->assertOk()
            ->assertSee('Fight details')
            ->assertSee('Meron value')
            ->assertSee('Wala payout')
            ->assertSee('Draw payout')
            ->assertSee('CANCELLED')
            ->assertSee('Winning side');
    }
}
