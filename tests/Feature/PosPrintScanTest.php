<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Fight;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PosPrintScanTest extends TestCase
{
    use RefreshDatabase;

    private function openFight(): Fight
    {
        $this->seed();

        $gm = User::where('username', 'gm.north')->firstOrFail();

        $event = Event::create([
            'code' => 'REG-PRINT',
            'name' => 'Print Card',
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

    public function test_a_bet_queues_the_receipt_for_automatic_printing(): void
    {
        $fight = $this->openFight();
        $teller = User::where('username', 'pos01')->firstOrFail();

        if (! $teller->tellerProfile->openShift()) {
            $this->actingAs($teller)->post('/pos/shift/open')->assertRedirect();
        }

        $response = $this->actingAs($teller)
            ->post('/pos/bets', ['fight_id' => $fight->id, 'side' => 'red', 'amount' => 100])
            ->assertRedirect()
            ->assertSessionHas('status');

        $receipt = session('print_receipt');
        $this->assertNotNull($receipt);
        $this->assertStringContainsString('/pos/bets/', $receipt);
        $this->assertStringContainsString('/receipt', $receipt);

        // The terminal reads that URL and prints it in a hidden frame.
        $this->actingAs($teller)
            ->get('/pos')
            ->assertOk()
            ->assertSee('data-auto-print', false)
            ->assertSee($receipt);
    }

    public function test_the_terminal_celebrates_an_accepted_bet_with_the_amount(): void
    {
        $fight = $this->openFight();
        $teller = User::where('username', 'pos01')->firstOrFail();

        if (! $teller->tellerProfile->openShift()) {
            $this->actingAs($teller)->post('/pos/shift/open')->assertRedirect();
        }

        $this->actingAs($teller)
            ->post('/pos/bets', ['fight_id' => $fight->id, 'side' => 'red', 'amount' => 12500])
            ->assertRedirect();

        $success = session('bet_success');
        $this->assertSame(12500, $success['amount']);
        $this->assertSame('MERON', $success['side']);

        $this->actingAs($teller)
            ->get('/pos')
            ->assertOk()
            ->assertSee('data-bet-success', false)
            ->assertSee('bet-success-amount')
            ->assertSee('12,500')
            ->assertSee('Bet accepted');
    }

    public function test_the_receipt_carries_a_manual_code_beside_the_barcode(): void
    {
        $fight = $this->openFight();
        $teller = User::where('username', 'pos01')->firstOrFail();

        if (! $teller->tellerProfile->openShift()) {
            $this->actingAs($teller)->post('/pos/shift/open')->assertRedirect();
        }

        $this->actingAs($teller)
            ->post('/pos/bets', ['fight_id' => $fight->id, 'side' => 'blue', 'amount' => 100])
            ->assertRedirect();

        $bet = $fight->bets()->firstOrFail();

        $this->actingAs($teller)
            ->get('/pos/bets/'.$bet->id.'/receipt')
            ->assertOk()
            ->assertSee('MANUAL CODE')
            ->assertSee('BET-'.str_pad((string) $bet->id, 6, '0', STR_PAD_LEFT), false);
    }

    public function test_the_pos_and_lookup_screens_expose_the_f9_scan_box(): void
    {
        $this->seed();
        $teller = User::where('username', 'pos01')->firstOrFail();

        $this->actingAs($teller)
            ->get('/pos')
            ->assertOk()
            ->assertSee('data-scan-input', false)
            ->assertSee('data-scan-url', false)
            ->assertSee('F9');

        $this->actingAs($teller)
            ->get('/pos/lookup')
            ->assertOk()
            ->assertSee('data-scan-input', false)
            ->assertSee('MANUAL CODE')
            ->assertSee('F9');
    }
}
