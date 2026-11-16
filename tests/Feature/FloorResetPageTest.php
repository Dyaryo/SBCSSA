<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Event;
use App\Models\User;
use App\Models\Workstation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class FloorResetPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_supervisor_can_see_the_floor_reset_page(): void
    {
        $this->seed();
        $supervisor = User::where('username', 'supervisor')->firstOrFail();

        $this->actingAs($supervisor)
            ->get('/supervisor/maintenance')
            ->assertOk()
            ->assertSee('Floor reset')
            ->assertSee('What is on the floor now')
            ->assertSee('Type RESET to confirm')
            ->assertSee('Kept:')
            ->assertSee('workstation(s)');
    }

    public function test_the_reset_needs_the_exact_confirmation_word(): void
    {
        $this->seed();
        $supervisor = User::where('username', 'supervisor')->firstOrFail();
        $events = Event::count();

        $this->actingAs($supervisor)
            ->post('/supervisor/maintenance/reset', ['confirm' => 'reset', 'till' => 0])
            ->assertSessionHasErrors('confirm');

        $this->assertSame($events, Event::count(), 'Nothing may be cleared without the confirmation word.');
        $this->assertDatabaseCount('bets', DB::table('bets')->count());
    }

    public function test_a_floor_reset_clears_the_floor_but_keeps_accounts_and_terminals(): void
    {
        $this->seed();

        $supervisor = User::where('username', 'supervisor')->firstOrFail();
        $accounts = User::count();
        $workstations = Workstation::count();
        $this->assertGreaterThan(0, Event::count());

        $this->actingAs($supervisor)
            ->post('/supervisor/maintenance/reset', ['confirm' => 'RESET', 'till' => 2500])
            ->assertRedirect('/supervisor/maintenance')
            ->assertSessionHas('status');

        $this->assertSame(0, Event::count());
        $this->assertSame(0, DB::table('fights')->count());
        $this->assertSame(0, DB::table('bets')->count());
        $this->assertSame(0, DB::table('shift_sessions')->count());
        $this->assertSame(0, DB::table('wallet_transactions')->count());
        $this->assertSame(0, DB::table('report_snapshots')->count());

        $this->assertSame($accounts, User::count(), 'Accounts must survive');
        $this->assertSame($workstations, Workstation::count(), 'Workstations must survive');
        $this->assertSame(2500, (int) DB::table('virtual_wallets')->min('balance_credits'));
        $this->assertSame(2500, (int) DB::table('virtual_wallets')->max('balance_credits'));

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'floor.reset',
            'actor_user_id' => $supervisor->id,
        ]);

        $this->assertAuthenticatedAs($supervisor);
    }

    public function test_the_newly_empty_floor_reads_as_empty(): void
    {
        $this->seed();
        $supervisor = User::where('username', 'supervisor')->firstOrFail();

        $this->actingAs($supervisor)
            ->post('/supervisor/maintenance/reset', ['confirm' => 'RESET', 'till' => 0])
            ->assertRedirect();

        $this->get('/supervisor/maintenance')
            ->assertOk()
            ->assertSee('What is on the floor now')
            ->assertSee('wallet is set to the starting', false);

        $this->assertSame(0, Event::count());
    }

    public function test_only_a_supervisor_can_reset_the_floor(): void
    {
        $this->seed();

        foreach (['gm.north', 'pos01'] as $username) {
            $user = User::where('username', $username)->firstOrFail();

            $this->actingAs($user)->get('/supervisor/maintenance')->assertForbidden();
            $this->actingAs($user)->post('/supervisor/maintenance/reset', ['confirm' => 'RESET'])->assertForbidden();
        }

        $this->assertGreaterThan(0, Event::count());
        $this->assertSame(0, AuditLog::where('action', 'floor.reset')->count());
    }
}
