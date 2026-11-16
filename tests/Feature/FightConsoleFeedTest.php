<?php

namespace Tests\Feature;

use App\Models\Bet;
use App\Models\Event;
use App\Models\Fight;
use App\Models\User;
use App\Services\BettingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FightConsoleFeedTest extends TestCase
{
    use RefreshDatabase;

    private function card(?float $stopRatio = null): Event
    {
        $this->seed();

        $gm = User::where('username', 'gm.north')->firstOrFail();

        return Event::create([
            'code' => 'REG-LIYA',
            'name' => 'Console Feed Card',
            'type' => Event::TYPE_REGULAR,
            'game_master_id' => $gm->gameMasterProfile->id,
            'starts_at' => now(),
            'stop_ratio' => $stopRatio,
            'status' => Event::STATUS_OPEN,
        ]);
    }

    private function fightIn(Event $event, float $red, float $blue, int $no = 1, ?float $stop = null): Fight
    {
        return Fight::create([
            'event_id' => $event->id,
            'fight_no' => $no,
            'odds_red' => $red,
            'odds_blue' => $blue,
            'stop_ratio' => $stop,
            'status' => Fight::STATUS_BETTING_OPEN,
        ]);
    }

    public function test_the_console_shows_posted_and_capped_payout_rates(): void
    {
        $fight = $this->fightIn($this->card(2.000), 1.850, 2.050, 1, 2.000);
        $gm = User::where('username', 'gm.north')->firstOrFail();

        $this->actingAs($gm)
            ->get('/gm/fights/'.$fight->id)
            ->assertOk()
            ->assertSee('posted odds')
            ->assertSee('PAYS 1.850×')
            ->assertSee('PAYS 2.000× · capped')
            ->assertSee('Bet stopper 2.000× caps this fight.')
            ->assertSee('Wala posted 2.050×')
            ->assertSee('pays 2.000×.');
    }

    public function test_the_console_feed_changes_when_the_stop_ratio_changes(): void
    {
        $fight = $this->fightIn($this->card(), 1.850, 2.050);
        $gm = User::where('username', 'gm.north')->firstOrFail();

        $before = $this->actingAs($gm)->getJson('/gm/fights/'.$fight->id.'/feed')->assertOk()->json('signature');

        $fight->update(['stop_ratio' => 2.000]);

        $after = $this->actingAs($gm)->getJson('/gm/fights/'.$fight->id.'/feed')->assertOk()->json('signature');
        $this->assertNotSame($before, $after);
    }

    public function test_the_console_does_not_label_corners_liyamado_or_dehado(): void
    {
        $fight = $this->fightIn($this->card(), 1.850, 2.050);
        $gm = User::where('username', 'gm.north')->firstOrFail();

        $this->actingAs($gm)
            ->get('/gm/fights/'.$fight->id)
            ->assertOk()
            ->assertDontSee('LIYAMADO')
            ->assertDontSee('DEHADO')
            ->assertSee('Meron value')
            ->assertSee('Wala value');
    }

    public function test_the_board_does_not_label_corners_liyamado_or_dehado(): void
    {
        $fight = $this->fightIn($this->card(), 1.850, 2.050);

        $this->get('/board?event_id='.$fight->event_id)
            ->assertOk()
            ->assertDontSee('LIYAMADO')
            ->assertDontSee('DEHADO')
            ->assertSee('MERON')
            ->assertSee('WALA');
    }

    public function test_the_feed_signature_changes_when_a_teller_takes_a_bet(): void
    {
        $fight = $this->fightIn($this->card(), 1.850, 2.050);
        $gm = User::where('username', 'gm.north')->firstOrFail();
        $teller = User::where('username', 'pos01')->firstOrFail();

        $before = $this->actingAs($gm)->getJson('/gm/fights/'.$fight->id.'/feed')->assertOk()->json('signature');

        if (! $teller->tellerProfile->openShift()) {
            $this->actingAs($teller)->post('/pos/shift/open')->assertRedirect();
        }

        app(BettingService::class)->placeBet($teller, $fight, Fight::SIDE_RED, 100);

        $after = $this->actingAs($gm)->getJson('/gm/fights/'.$fight->id.'/feed')->assertOk()->json('signature');

        $this->assertNotSame($before, $after);
        $this->assertSame(1, Bet::where('fight_id', $fight->id)->count());
    }

    public function test_another_game_master_cannot_read_the_feed(): void
    {
        $fight = $this->fightIn($this->card(), 1.850, 2.050);
        $south = User::where('username', 'gm.south')->firstOrFail();

        $this->actingAs($south)->getJson('/gm/fights/'.$fight->id.'/feed')->assertForbidden();
    }

    public function test_the_console_carries_the_live_attributes(): void
    {
        $fight = $this->fightIn($this->card(), 1.850, 2.050);
        $gm = User::where('username', 'gm.north')->firstOrFail();

        $this->actingAs($gm)
            ->get('/gm/fights/'.$fight->id)
            ->assertOk()
            ->assertSee('data-gm-fight', false)
            ->assertSee('data-feed-url', false)
            ->assertSee('Bets from the windows appear here as they are taken');
    }
}
