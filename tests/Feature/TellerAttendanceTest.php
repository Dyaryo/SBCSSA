<?php

namespace Tests\Feature;

use App\Models\Bet;
use App\Models\Event;
use App\Models\Fight;
use App\Models\ShiftSession;
use App\Models\TellerProfile;
use App\Models\User;
use App\Models\Workstation;
use App\Services\BettingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TellerAttendanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_new_teller_can_be_bound_to_a_terminal_at_account_creation(): void
    {
        $this->seed();

        $supervisor = User::where('username', 'supervisor')->firstOrFail();
        $gm = User::where('username', 'gm.north')->firstOrFail();
        $workstation = Workstation::where('code', 'POS-001')->firstOrFail();

        $this->actingAs($supervisor)->post('/supervisor/accounts', [
            'name' => 'Terminal Teller',
            'username' => 'pos09',
            'password' => 'Teller@2026',
            'role' => 'teller',
            'teller_code' => 'POS 009',
            'game_master_id' => $gm->gameMasterProfile->id,
            'workstation_id' => $workstation->id,
            'initial_credits' => 5000,
        ])->assertRedirect()->assertSessionHasNoErrors();

        $teller = TellerProfile::where('teller_code', 'POS 009')->firstOrFail();

        $this->assertSame($workstation->id, $teller->workstation_id);
        $this->assertSame($teller->id, $workstation->fresh()->assigned_teller_id);
        $this->assertSame(5000, $teller->user->creditBalance());
    }

    public function test_signing_in_on_the_assigned_terminal_opens_the_shift(): void
    {
        $this->seed();

        $supervisor = User::where('username', 'supervisor')->firstOrFail();
        $gm = User::where('username', 'gm.north')->firstOrFail();
        $workstation = Workstation::where('code', 'POS-003')->firstOrFail();

        $this->actingAs($supervisor)->post('/supervisor/accounts', [
            'name' => 'Clock Teller',
            'username' => 'pos08',
            'password' => 'Teller@2026',
            'role' => 'teller',
            'teller_code' => 'POS 008',
            'game_master_id' => $gm->gameMasterProfile->id,
            'workstation_id' => $workstation->id,
        ])->assertRedirect();

        $this->post('/logout');
        $this->post('/login', ['username' => 'pos08', 'password' => 'Teller@2026'])
            ->assertRedirect('/pos')
            ->assertSessionHas('status');

        $teller = TellerProfile::where('teller_code', 'POS 008')->firstOrFail();
        $shift = $teller->openShift();

        $this->assertNotNull($shift);
        $this->assertSame($workstation->id, $shift->workstation_id);
        $this->assertStringContainsString('Attendance', (string) $shift->opening_note);
    }

    public function test_signing_out_closes_the_shift_and_seals_it(): void
    {
        $this->seed();

        // pos03 has no seeded slips, so its till has nothing left to settle.
        $teller = User::where('username', 'pos03')->firstOrFail();
        $workstation = Workstation::where('code', 'POS-003')->firstOrFail();

        $teller->tellerProfile->update(['workstation_id' => $workstation->id]);
        $workstation->update(['assigned_teller_id' => $teller->tellerProfile->id]);

        $this->post('/login', ['username' => 'pos03', 'password' => 'Teller@2026'])->assertRedirect('/pos');
        $this->assertNotNull($teller->tellerProfile->fresh()->openShift());

        $this->post('/logout')->assertRedirect('/login');

        $shift = ShiftSession::where('teller_id', $teller->tellerProfile->id)->latest('id')->firstOrFail();

        $this->assertSame(ShiftSession::STATUS_CLOSED, $shift->status);
        $this->assertStringContainsString('Attendance', (string) $shift->closing_note);
        $this->assertDatabaseHas('report_snapshots', ['scope' => 'teller_shift']);
    }

    public function test_signing_out_leaves_a_shift_open_when_bets_are_unsettled(): void
    {
        $this->seed();

        $teller = User::where('username', 'pos01')->firstOrFail();
        $gm = User::where('username', 'gm.north')->firstOrFail();
        $workstation = Workstation::where('code', 'POS-001')->firstOrFail();

        $teller->tellerProfile->update(['workstation_id' => $workstation->id]);

        $event = Event::create([
            'code' => 'REG-ATT',
            'name' => 'Attendance Card',
            'type' => Event::TYPE_REGULAR,
            'game_master_id' => $gm->gameMasterProfile->id,
            'starts_at' => now(),
            'status' => Event::STATUS_OPEN,
        ]);

        $fight = Fight::create([
            'event_id' => $event->id,
            'fight_no' => 1,
            'odds_red' => 1.850,
            'odds_blue' => 2.050,
            'status' => Fight::STATUS_BETTING_OPEN,
        ]);

        $this->post('/login', ['username' => 'pos01', 'password' => 'Teller@2026'])->assertRedirect('/pos');

        app(BettingService::class)->placeBet($teller, $fight, Fight::SIDE_RED, 100);

        $this->post('/logout')->assertRedirect('/login');

        $this->assertNotNull($teller->tellerProfile->fresh()->openShift());
        $this->assertSame(1, Bet::where('fight_id', $fight->id)->count());
    }

    public function test_the_login_page_loads_the_fingerprint_script(): void
    {
        $this->get('/login')->assertOk()->assertSee('js/app.js', false);
    }
}
