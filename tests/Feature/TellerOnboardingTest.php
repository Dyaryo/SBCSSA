<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\GameMasterProfile;
use App\Models\ShiftSession;
use App\Models\TellerProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * POS onboarding belongs to the Supervisor: the Game Master runs fights and
 * keeps a read-only roster.
 */
class TellerOnboardingTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_game_master_can_no_longer_create_or_fund_tellers(): void
    {
        $this->seed();

        $gm = User::where('username', 'gm.north')->firstOrFail();
        $teller = TellerProfile::where('teller_code', 'POS 001')->firstOrFail();
        $balanceBefore = $teller->user->creditBalance();

        $response = $this->actingAs($gm)->post('/gm/tellers', [
            'name' => 'Rogue Teller',
            'username' => 'rogue.teller',
            'password' => 'Password@123',
            'teller_code' => 'POS 099',
        ]);

        // The creation route is gone; only the read-only listing still answers on
        // that path, so a POST is rejected rather than creating an account.
        $this->assertContains($response->status(), [404, 405]);

        $this->actingAs($gm)
            ->post('/gm/tellers/'.$teller->id.'/credits', ['amount' => 5000])
            ->assertNotFound();

        $this->assertDatabaseMissing('users', ['username' => 'rogue.teller']);
        $this->assertSame($balanceBefore, $teller->user->fresh()->creditBalance());
    }

    public function test_the_game_master_roster_has_no_onboarding_or_grant_controls(): void
    {
        $this->seed();

        $gm = User::where('username', 'gm.north')->firstOrFail();

        $this->actingAs($gm)
            ->get('/gm/tellers')
            ->assertOk()
            ->assertSee('POS 001')
            ->assertDontSee('Create POS account')
            ->assertDontSee('name="amount"', false)
            ->assertDontSee('name="teller_code"', false)
            ->assertSee('Supervisor');
    }

    public function test_the_supervisor_onboards_a_teller_with_opening_credits(): void
    {
        $this->seed();

        $supervisor = User::where('username', 'supervisor')->firstOrFail();
        $gmProfile = GameMasterProfile::firstOrFail();

        $this->actingAs($supervisor)->post('/supervisor/accounts', [
            'name' => 'New Teller',
            'username' => 'pos.new',
            'password' => 'Password@123',
            'role' => 'teller',
            'teller_code' => 'pos 010',
            'initial_credits' => 2500,
        ])->assertRedirect();

        $user = User::where('username', 'pos.new')->firstOrFail();

        $this->assertSame(User::ROLE_TELLER, $user->role);
        $this->assertSame(2500, $user->creditBalance());
        // Tills are no longer tied to a Game Master when they are created.
        $this->assertDatabaseHas('teller_profiles', [
            'user_id' => $user->id,
            'teller_code' => 'POS 010',
            'game_master_id' => null,
        ]);
    }

    public function test_the_supervisor_can_grant_credits_to_a_teller(): void
    {
        $this->seed();

        $supervisor = User::where('username', 'supervisor')->firstOrFail();
        $teller = TellerProfile::where('teller_code', 'POS 001')->firstOrFail();
        $before = $teller->user->creditBalance();

        $this->actingAs($supervisor)
            ->post('/supervisor/accounts/'.$teller->user->id.'/credits', ['amount' => 750])
            ->assertRedirect();

        $this->assertSame($before + 750, $teller->user->fresh()->creditBalance());
        $this->assertSame(1, AuditLog::where('action', 'wallet.seeded')
            ->where('entity_id', $teller->user->id)
            ->count());
    }

    public function test_a_single_teller_top_up_is_capped_at_one_hundred_thousand(): void
    {
        $this->seed();

        $supervisor = User::where('username', 'supervisor')->firstOrFail();
        $teller = TellerProfile::where('teller_code', 'POS 001')->firstOrFail();
        $before = $teller->user->creditBalance();

        $this->actingAs($supervisor)
            ->post('/supervisor/accounts/'.$teller->user->id.'/credits', ['amount' => 100001])
            ->assertSessionHasErrors('amount');

        $this->assertSame($before, $teller->user->fresh()->creditBalance());

        $this->actingAs($supervisor)
            ->post('/supervisor/accounts/'.$teller->user->id.'/credits', ['amount' => 100000])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertSame($before + 100000, $teller->user->fresh()->creditBalance());
    }

    public function test_the_supervisor_can_review_a_tellers_top_up_history(): void
    {
        $this->seed();

        $supervisor = User::where('username', 'supervisor')->firstOrFail();
        $teller = TellerProfile::where('teller_code', 'POS 001')->firstOrFail();
        $other = TellerProfile::where('teller_code', 'POS 002')->firstOrFail();

        $this->actingAs($supervisor)->post('/supervisor/accounts/'.$teller->user->id.'/credits', [
            'amount' => 750,
            'reason' => 'Opening float for Friday',
        ])->assertRedirect();

        $this->actingAs($supervisor)->post('/supervisor/accounts/'.$other->user->id.'/credits', [
            'amount' => 500,
            'reason' => 'Another teller grant',
        ])->assertRedirect();

        $this->actingAs($supervisor)
            ->get(route('supervisor.accounts.topups', $teller->user))
            ->assertOk()
            ->assertSee('Top-up history')
            ->assertSee('POS 001')
            ->assertSee('Opening float for Friday')
            ->assertSee('+750')
            ->assertSee($supervisor->name)
            ->assertDontSee('Another teller grant');
    }

    public function test_a_teller_still_cannot_reach_the_onboarding_screens(): void
    {
        $this->seed();

        $tellerUser = User::where('username', 'pos01')->firstOrFail();

        $this->actingAs($tellerUser)->get('/supervisor/accounts')->assertForbidden();
        $this->actingAs($tellerUser)->get(route('supervisor.accounts.topups', $tellerUser))->assertForbidden();
        $this->actingAs($tellerUser)->get('/gm/tellers')->assertForbidden();
    }

    public function test_the_game_master_can_no_longer_reset_passwords_suspend_or_close_shifts(): void
    {
        $this->seed();

        $gm = User::where('username', 'gm.north')->firstOrFail();
        $teller = TellerProfile::where('teller_code', 'POS 001')->firstOrFail();
        $shift = ShiftSession::where('teller_id', $teller->id)->where('status', ShiftSession::STATUS_OPEN)->firstOrFail();

        $hashBefore = $teller->user->password;

        $this->actingAs($gm)->post('/gm/tellers/'.$teller->id.'/password', ['password' => 'Rogue@2026'])->assertNotFound();
        $this->actingAs($gm)->patch('/gm/tellers/'.$teller->id.'/status', ['status' => 'suspended'])->assertNotFound();
        $this->actingAs($gm)->post('/gm/shifts/'.$shift->id.'/close', ['reason' => 'rogue close'])->assertNotFound();

        $this->assertSame($hashBefore, $teller->user->fresh()->password);
        $this->assertSame(User::STATUS_ACTIVE, $teller->user->fresh()->status);
        $this->assertSame(ShiftSession::STATUS_OPEN, $shift->fresh()->status);
    }

    public function test_the_game_master_roster_offers_no_interventions(): void
    {
        $this->seed();

        $gm = User::where('username', 'gm.north')->firstOrFail();

        $this->actingAs($gm)
            ->get('/gm/tellers')
            ->assertOk()
            ->assertSee('This screen is read-only')
            ->assertDontSee('name="password"', false)
            ->assertDontSee('name="amount"', false)
            ->assertDontSee('/close"', false);
    }

    public function test_the_supervisor_resets_a_teller_password(): void
    {
        $this->seed();

        $supervisor = User::where('username', 'supervisor')->firstOrFail();
        $teller = TellerProfile::where('teller_code', 'POS 001')->firstOrFail();

        $this->actingAs($supervisor)
            ->post('/supervisor/accounts/'.$teller->user_id.'/password', ['password' => 'NewPass@2026'])
            ->assertRedirect();

        $this->assertTrue(Hash::check('NewPass@2026', $teller->user->fresh()->password));
        $this->assertGreaterThan(0, AuditLog::where('action', 'account.password_reset')
            ->where('entity_id', $teller->user_id)
            ->count());
    }

    public function test_a_supervisor_suspension_also_suspends_the_pos_profile(): void
    {
        $this->seed();

        $supervisor = User::where('username', 'supervisor')->firstOrFail();
        $teller = TellerProfile::where('teller_code', 'POS 001')->firstOrFail();

        $this->actingAs($supervisor)
            ->patch('/supervisor/accounts/'.$teller->user_id.'/status', ['status' => 'suspended'])
            ->assertRedirect();

        $this->assertSame(User::STATUS_SUSPENDED, $teller->user->fresh()->status);
        $this->assertSame('suspended', $teller->fresh()->status);
    }

    public function test_the_supervisor_force_closes_a_stuck_shift(): void
    {
        $this->seed();

        $supervisor = User::where('username', 'supervisor')->firstOrFail();
        $shift = ShiftSession::where('status', ShiftSession::STATUS_OPEN)->firstOrFail();

        $this->actingAs($supervisor)
            ->post('/supervisor/shifts/'.$shift->id.'/close', ['reason' => 'teller walked away'])
            ->assertRedirect();

        $closed = $shift->fresh();

        $this->assertSame(ShiftSession::STATUS_FORCE_CLOSED, $closed->status);
        $this->assertSame('teller walked away', $closed->closed_reason);
        $this->assertSame(1, AuditLog::where('action', 'shift.force_closed')->where('entity_id', $shift->id)->count());
    }

    public function test_the_supervisor_sees_every_open_shift(): void
    {
        $this->seed();

        $supervisor = User::where('username', 'supervisor')->firstOrFail();

        $this->actingAs($supervisor)
            ->get('/supervisor/shifts')
            ->assertOk()
            ->assertSee('Open shifts')
            ->assertSee('POS 001');
    }
}
