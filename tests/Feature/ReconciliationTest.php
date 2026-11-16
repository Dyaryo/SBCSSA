<?php

namespace Tests\Feature;

use App\Models\TellerProfile;
use App\Models\VirtualWallet;
use App\Services\ReconciliationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ReconciliationTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_day_reconciles_cleanly_on_seeded_data(): void
    {
        $this->seed();

        $data = app(ReconciliationService::class)->build(now()->toDateString());

        $this->assertNotEmpty($data['rows']);
        $this->assertGreaterThan(0, $data['totals']['bets']);
        $this->assertSame(0, $data['totals']['issues'], 'Seeded bets and ledger should agree.');
    }

    public function test_an_extra_ledger_row_is_flagged_as_a_mismatch(): void
    {
        $this->seed();

        $teller = TellerProfile::where('teller_code', 'POS 001')->firstOrFail();
        $wallet = VirtualWallet::where('user_id', $teller->user_id)->firstOrFail();

        DB::table('wallet_transactions')->insert([
            'wallet_id' => $wallet->id,
            'user_id' => $teller->user_id,
            'type' => 'bet_hold',
            'direction' => 'debit',
            'amount_credits' => 12345,
            'balance_after' => $wallet->balance_credits,
            'reference_no' => 'TEST-TAMPER',
            'created_at' => now(),
        ]);

        $data = app(ReconciliationService::class)->build(now()->toDateString());

        $this->assertGreaterThan(0, $data['totals']['issues']);
    }

    public function test_a_supervisor_can_open_the_reconciliation_screen(): void
    {
        $this->seed();

        $this->actingAs(\App\Models\User::where('username', 'supervisor')->firstOrFail())
            ->get('/supervisor/reconciliation')
            ->assertOk()
            ->assertSee('End-of-day reconciliation');
    }

    public function test_a_teller_cannot_open_reconciliation(): void
    {
        $this->seed();

        $this->actingAs(\App\Models\User::where('username', 'pos01')->firstOrFail())
            ->get('/supervisor/reconciliation')
            ->assertForbidden();
    }
}
