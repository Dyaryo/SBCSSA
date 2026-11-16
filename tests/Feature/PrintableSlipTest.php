<?php

namespace Tests\Feature;

use App\Models\Bet;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PrintableSlipTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_teller_can_print_a_receipt_for_their_own_bet(): void
    {
        $this->seed();

        $bet = Bet::whereHas('teller', fn ($q) => $q->where('teller_code', 'POS 001'))
            ->firstOrFail();

        $this->actingAs(User::where('username', 'pos01')->firstOrFail())
            ->get('/pos/bets/'.$bet->id.'/receipt')
            ->assertOk()
            ->assertSee('BET RECEIPT')
            ->assertSee('Play money only — not a cash voucher')
            ->assertSee('cannot be bought, sold, redeemed')
            ->assertSee(number_format($bet->amount_credits));
    }

    public function test_a_teller_cannot_print_another_tellers_receipt(): void
    {
        $this->seed();

        $foreign = Bet::whereHas('teller', fn ($q) => $q->where('teller_code', 'POS 002'))
            ->firstOrFail();

        $this->actingAs(User::where('username', 'pos01')->firstOrFail())
            ->get('/pos/bets/'.$foreign->id.'/receipt')
            ->assertForbidden();
    }

    public function test_a_shift_closeout_slip_totals_the_bets(): void
    {
        $this->seed();

        $shift = User::where('username', 'pos01')->firstOrFail()
            ->tellerProfile->shifts()->latest('id')->firstOrFail();

        $this->actingAs(User::where('username', 'pos01')->firstOrFail())
            ->get('/pos/shifts/'.$shift->id.'/closeout')
            ->assertOk()
            ->assertSee('SHIFT CLOSEOUT SLIP');
    }
}
