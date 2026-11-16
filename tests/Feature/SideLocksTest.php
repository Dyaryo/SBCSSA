<?php

namespace Tests\Feature;

use App\Exceptions\BettingException;
use App\Models\Bet;
use App\Models\Event;
use App\Models\Fight;
use App\Models\User;
use App\Services\BettingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * When the windows all pile onto one corner, the Game Master can shut that
 * side on its own instead of closing the whole fight.
 */
class SideLocksTest extends TestCase
{
    use RefreshDatabase;

    private function openFight(): Fight
    {
        $this->seed();

        $north = User::where('username', 'gm.north')->firstOrFail();

        $event = Event::create([
            'code' => 'REG-LOCK01',
            'name' => 'Lock Test Card',
            'type' => Event::TYPE_REGULAR,
            'game_master_id' => $north->gameMasterProfile->id,
            'starts_at' => now(),
            'status' => Event::STATUS_OPEN,
        ]);

        return Fight::create([
            'event_id' => $event->id,
            'fight_no' => 1,
            'odds_red' => 1.850,
            'odds_blue' => 1.950,
            'status' => Fight::STATUS_BETTING_OPEN,
        ]);
    }

    public function test_the_game_master_locks_one_side_and_the_console_says_so(): void
    {
        $fight = $this->openFight();
        $gm = User::where('username', 'gm.north')->firstOrFail();

        $this->actingAs($gm)
            ->post('/gm/fights/'.$fight->id.'/side-lock', ['side' => 'red', 'locked' => 1])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $fight->refresh();
        $this->assertTrue($fight->isSideLocked(Fight::SIDE_RED));
        $this->assertFalse($fight->isSideLocked(Fight::SIDE_BLUE));

        $this->actingAs($gm)
            ->get('/gm/fights/'.$fight->id)
            ->assertOk()
            ->assertSee('LOCKED')
            ->assertSee('Unlock Meron')
            ->assertSee('Lock Wala');
    }

    public function test_a_locked_side_takes_no_more_bets_but_the_other_side_does(): void
    {
        $fight = $this->openFight();
        $gm = User::where('username', 'gm.north')->firstOrFail();
        $teller = User::where('username', 'pos01')->firstOrFail();

        $this->actingAs($gm)->post('/gm/fights/'.$fight->id.'/side-lock', ['side' => 'red', 'locked' => 1]);

        if (! $teller->tellerProfile->openShift()) {
            $this->actingAs($teller)->post('/pos/shift/open')->assertRedirect();
        }

        $this->actingAs($teller)
            ->from('/pos')
            ->post('/pos/bets', ['fight_id' => $fight->id, 'side' => 'red', 'amount' => 100])
            ->assertRedirect('/pos')
            ->assertSessionHas('error');

        $this->assertSame(0, Bet::where('fight_id', $fight->id)->count());

        // The open side still does business and the window shows the lock.
        $this->actingAs($teller)->get('/pos')->assertOk()->assertSee('LOCKED');

        $this->actingAs($teller)
            ->post('/pos/bets', ['fight_id' => $fight->id, 'side' => 'blue', 'amount' => 100])
            ->assertRedirect()
            ->assertSessionHas('status');

        $this->assertSame(1, Bet::where('fight_id', $fight->id)->where('side', 'blue')->count());

        // The service enforces it too, not only the button.
        $this->expectException(BettingException::class);
        app(BettingService::class)->placeBet($teller, $fight, Fight::SIDE_RED, 100);
    }

    public function test_unlocking_lets_the_side_take_bets_again(): void
    {
        $fight = $this->openFight();
        $gm = User::where('username', 'gm.north')->firstOrFail();
        $teller = User::where('username', 'pos01')->firstOrFail();

        $this->actingAs($gm)->post('/gm/fights/'.$fight->id.'/side-lock', ['side' => 'blue', 'locked' => 1]);
        $this->actingAs($gm)->post('/gm/fights/'.$fight->id.'/side-lock', ['side' => 'blue', 'locked' => 0]);

        $this->assertFalse($fight->fresh()->isSideLocked(Fight::SIDE_BLUE));

        if (! $teller->tellerProfile->openShift()) {
            $this->actingAs($teller)->post('/pos/shift/open')->assertRedirect();
        }

        $this->actingAs($teller)
            ->post('/pos/bets', ['fight_id' => $fight->id, 'side' => 'blue', 'amount' => 100])
            ->assertRedirect()
            ->assertSessionHas('status');

        $this->assertSame(1, Bet::where('fight_id', $fight->id)->count());
    }

    public function test_a_side_cannot_be_locked_outside_an_open_betting_window(): void
    {
        $fight = $this->openFight();
        $fight->update(['status' => Fight::STATUS_SCHEDULED]);

        $gm = User::where('username', 'gm.north')->firstOrFail();

        $this->actingAs($gm)
            ->post('/gm/fights/'.$fight->id.'/side-lock', ['side' => 'red', 'locked' => 1])
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertFalse($fight->fresh()->isSideLocked(Fight::SIDE_RED));
    }
}
