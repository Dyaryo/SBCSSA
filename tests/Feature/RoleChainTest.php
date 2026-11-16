<?php

namespace Tests\Feature;

use App\Models\Bet;
use App\Models\Event;
use App\Models\Fight;
use App\Models\ShiftSession;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The three roles are one chain: the Supervisor onboards the people, the Game
 * Master runs the card, the teller takes the money, and the same money shows up
 * back on the Supervisor's desk.
 */
class RoleChainTest extends TestCase
{
    use RefreshDatabase;

    public function test_supervisor_gm_and_teller_share_one_book(): void
    {
        $this->seed();

        $supervisor = User::where('username', 'supervisor')->firstOrFail();

        // 1. Supervisor creates the Game Master.
        $this->actingAs($supervisor)->post('/supervisor/accounts', [
            'name' => 'Chain Master',
            'username' => 'gm.chain',
            'password' => 'Password@123',
            'role' => 'game_master',
            'employee_no' => 'EMP-900',
        ])->assertRedirect()->assertSessionHasNoErrors();

        $gm = User::where('username', 'gm.chain')->firstOrFail();
        $gmProfile = $gm->gameMasterProfile;

        // 2. Supervisor creates a pit till, funded. It is not attached to a Game
        // Master: the desk works the card that is open, whoever runs it.
        $this->actingAs($supervisor)->post('/supervisor/accounts', [
            'name' => 'Chain Teller',
            'username' => 'pos.chain',
            'password' => 'Password@123',
            'role' => 'teller',
            'teller_code' => 'pos 050',
            'initial_credits' => 5000,
        ])->assertRedirect()->assertSessionHasNoErrors();

        $teller = User::where('username', 'pos.chain')->firstOrFail();

        $this->assertSame(5000, $teller->creditBalance());
        $this->assertNull($teller->tellerProfile->game_master_id);

        // The unassigned till still belongs to the pit this Game Master runs.
        $this->actingAs($gm)->get('/gm/tellers')->assertOk()->assertSee('POS 050');

        // 3. The Game Master builds a card and opens a fight.
        $this->actingAs($gm)->post('/gm/events', [
            'type' => 'regular',
            'name' => 'Chain Card',
            'starts_at' => now()->toDateString(),
            'stop_ratio' => 1.400,
            'draw_ratio' => 7,
            'tv_total_bets' => 100,
        ])->assertRedirect()->assertSessionHasNoErrors();

        $event = Event::where('name', 'Chain Card')->firstOrFail();

        $this->actingAs($gm)->post('/gm/fights', [
            'event_id' => $event->id,
            'fight_no' => 1,
            'odds_red' => 2.000,
            'odds_blue' => 1.800,
        ])->assertRedirect()->assertSessionHasNoErrors();

        $fight = Fight::where('event_id', $event->id)->firstOrFail();
        $this->actingAs($gm)->post('/gm/fights/'.$fight->id.'/open')->assertRedirect();

        // 4. The teller sees that fight and takes a bet.
        $this->actingAs($teller)->post('/pos/shift/open')->assertRedirect();

        $shift = ShiftSession::where('teller_id', $teller->tellerProfile->id)
            ->where('status', ShiftSession::STATUS_OPEN)
            ->firstOrFail();

        $this->actingAs($teller)
            ->get('/pos')
            ->assertOk()
            ->assertSee('Chain Card')
            ->assertSee('DRAW');

        $this->actingAs($teller)->post('/pos/bets', [
            'fight_id' => $fight->id,
            'side' => 'red',
            'amount' => 100,
        ])->assertRedirect()->assertSessionHasNoErrors();

        $bet = Bet::where('fight_id', $fight->id)->firstOrFail();

        $this->assertSame(100, (int) $bet->amount_credits);
        $this->assertSame($shift->id, $bet->shift_session_id);
        $this->assertSame(4900, $teller->fresh()->creditBalance());

        // 5. The Game Master sees the teller's money on his own screens.
        $this->actingAs($gm)->get('/gm')->assertOk()->assertSee('100');
        $this->actingAs($gm)->get('/gm/monitor')->assertOk()->assertSee('POS 050');
        $this->actingAs($gm)->get('/gm/tellers')->assertOk()->assertSee('POS 050');
        $this->actingAs($gm)->get('/gm/shifts/'.$shift->id.'/closeout')->assertOk()->assertSee('POS 050');

        // 6. The Game Master grades; the payout lands in the teller's wallet.
        $this->actingAs($gm)->post('/gm/fights/'.$fight->id.'/lock')->assertRedirect();
        $this->actingAs($gm)->post('/gm/fights/'.$fight->id.'/result', ['result' => 'meron'])->assertRedirect();

        $this->assertSame(Bet::STATUS_WON, $bet->fresh()->status);
        // 2.000 posted odds, capped at the card's 1.400 stopper: 140 paid.
        $this->assertSame(140, (int) $bet->fresh()->potential_payout);
        $this->assertSame(5040, $teller->fresh()->creditBalance());

        // 7. The Supervisor sees the shift, the bet, the event and the report.
        $this->actingAs($supervisor)
            ->get('/supervisor/shifts')
            ->assertOk()
            ->assertSee('POS 050');

        $this->actingAs($supervisor)
            ->get('/supervisor/shifts/'.$shift->id)
            ->assertOk()
            ->assertSee('POS 050')
            ->assertSee('Chain Card')
            ->assertSee('100');

        $this->actingAs($supervisor)
            ->get('/supervisor/shifts/'.$shift->id.'/closeout')
            ->assertOk()
            ->assertSee('POS 050');

        $this->actingAs($supervisor)
            ->get('/supervisor/reports?scope=event&event_id='.$event->id)
            ->assertOk()
            ->assertSee('Chain Card')
            ->assertSee('100');

        $this->actingAs($supervisor)
            ->get('/supervisor/reports?scope=gm_daily&game_master_id='.$gmProfile->id.'&date='.now()->toDateString())
            ->assertOk()
            ->assertSee('Chain Master');

        // 8. Every role's actions are in the Supervisor's audit trail.
        $this->actingAs($supervisor)->get('/supervisor/audit')
            ->assertOk()
            ->assertSee('account.created')
            ->assertSee('event.created')
            ->assertSee('shift.opened')
            ->assertSee('fight.result_declared');
    }
}
