<?php

namespace Tests\Feature;

use App\Exceptions\BettingException;
use App\Models\Bet;
use App\Models\Event;
use App\Models\Fight;
use App\Models\ShiftSession;
use App\Models\User;
use App\Services\BettingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * A POS window belongs to one Game Master's pit: it lists and takes bets only
 * on that Game Master's cards, never another pit's fights.
 */
class TellerScopeTest extends TestCase
{
    use RefreshDatabase;

    private function eventFor(User $gmUser, string $name): Event
    {
        return Event::create([
            'code' => 'REG-'.strtoupper(substr(md5($name), 0, 6)),
            'name' => $name,
            'type' => Event::TYPE_REGULAR,
            'game_master_id' => $gmUser->gameMasterProfile->id,
            'starts_at' => now(),
            'status' => Event::STATUS_OPEN,
        ]);
    }

    private function openFight(Event $event, int $no): Fight
    {
        return Fight::create([
            'event_id' => $event->id,
            'fight_no' => $no,
            'odds_red' => 1.850,
            'odds_blue' => 1.950,
            'status' => Fight::STATUS_BETTING_OPEN,
        ]);
    }

    public function test_the_window_lists_only_its_own_game_masters_fights(): void
    {
        $this->seed();

        $north = User::where('username', 'gm.north')->firstOrFail();
        $south = User::where('username', 'gm.south')->firstOrFail();
        $teller = User::where('username', 'pos01')->firstOrFail();

        $ownEvent = $this->eventFor($north, 'North Card');
        $otherEvent = $this->eventFor($south, 'South Card');

        $this->openFight($ownEvent, 1);
        $this->openFight($otherEvent, 1);

        $this->actingAs($teller)
            ->get('/pos')
            ->assertOk()
            ->assertSee('North Card')
            ->assertDontSee('South Card');
    }

    public function test_a_bet_is_refused_on_another_game_masters_fight(): void
    {
        $this->seed();

        $north = User::where('username', 'gm.north')->firstOrFail();
        $south = User::where('username', 'gm.south')->firstOrFail();
        $teller = User::where('username', 'pos01')->firstOrFail();

        $otherFight = $this->openFight($this->eventFor($south, 'South Only'), 1);

        if (! $teller->tellerProfile->openShift()) {
            $this->actingAs($teller)->post('/pos/shift/open')->assertRedirect();
        }

        $balanceBefore = $teller->creditBalance();

        $this->actingAs($teller)
            ->from('/pos')
            ->post('/pos/bets', [
                'fight_id' => $otherFight->id,
                'side' => 'red',
                'amount' => 100,
            ])
            ->assertRedirect('/pos')
            ->assertSessionHas('error');

        $this->assertSame(0, Bet::where('fight_id', $otherFight->id)->count());
        $this->assertSame($balanceBefore, $teller->fresh()->creditBalance());

        // The service refuses it too, not just the HTTP layer.
        $this->expectException(BettingException::class);
        app(BettingService::class)->placeBet($teller, $otherFight, Fight::SIDE_RED, 100);
    }

    public function test_a_bet_on_its_own_game_masters_fight_still_works(): void
    {
        $this->seed();

        $north = User::where('username', 'gm.north')->firstOrFail();
        $teller = User::where('username', 'pos01')->firstOrFail();

        $ownFight = $this->openFight($this->eventFor($north, 'North Only'), 1);

        if (! $teller->tellerProfile->openShift()) {
            $this->actingAs($teller)->post('/pos/shift/open')->assertRedirect();
        }

        $this->actingAs($teller)
            ->post('/pos/bets', [
                'fight_id' => $ownFight->id,
                'side' => 'blue',
                'amount' => 100,
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertSame(1, Bet::where('fight_id', $ownFight->id)->count());
    }
}
