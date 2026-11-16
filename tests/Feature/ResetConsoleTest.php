<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ResetConsoleTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_reset_clears_operational_data_but_keeps_accounts(): void
    {
        $this->seed();
        $this->assertGreaterThan(0, Event::count());
        $accounts = User::count();

        $this->artisan('system:reset', ['--force' => true])->assertSuccessful();

        foreach ([
            'events', 'fights', 'fight_events', 'cocks', 'bets',
            'shift_sessions', 'wallet_transactions', 'audit_logs',
            'report_snapshots',
        ] as $table) {
            $this->assertSame(0, DB::table($table)->count(), $table.' should be empty after a reset');
        }

        $this->assertSame($accounts, User::count(), 'Logins must survive the reset');
        $this->assertSame(0, (int) DB::table('virtual_wallets')->max('balance_credits'));
    }

    public function test_a_reset_can_leave_a_starting_till_balance(): void
    {
        $this->seed();

        $this->artisan('system:reset', ['--force' => true, '--till' => 150000])->assertSuccessful();

        $this->assertSame(150000, (int) DB::table('virtual_wallets')->min('balance_credits'));
        $this->assertSame(150000, (int) DB::table('virtual_wallets')->max('balance_credits'));
    }

    public function test_the_console_reads_as_empty_after_a_reset(): void
    {
        $this->seed();
        $this->artisan('system:reset', ['--force' => true])->assertSuccessful();

        $this->actingAs(User::where('username', 'gm.north')->firstOrFail())
            ->get('/gm/events')
            ->assertOk()
            ->assertSee('No events yet.');

        $this->actingAs(User::where('username', 'gm.north')->firstOrFail())
            ->get('/gm/fights')
            ->assertOk()
            ->assertSee('No fights scheduled.');
    }
}
