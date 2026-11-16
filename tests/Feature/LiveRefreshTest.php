<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Fight;
use App\Models\User;
use App\Services\BettingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LiveRefreshTest extends TestCase
{
    use RefreshDatabase;

    private function openFight(): Fight
    {
        $this->seed();

        $gm = User::where('username', 'gm.north')->firstOrFail();

        $event = Event::create([
            'code' => 'REG-LIVE-REFRESH',
            'name' => 'Refresh Card',
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

    public function test_the_supervisor_overview_is_wired_to_its_live_feed(): void
    {
        $this->seed();
        $supervisor = User::where('username', 'supervisor')->firstOrFail();

        $response = $this->actingAs($supervisor)->get('/supervisor')->assertOk();
        $response->assertSee('data-live-refresh', false)->assertSee('/supervisor/feed');

        $before = $this->actingAs($supervisor)->getJson('/supervisor/feed')->assertOk()->json('signature');

        $teller = User::where('username', 'pos01')->firstOrFail();
        if (! $teller->tellerProfile->openShift()) {
            $this->actingAs($teller)->post('/pos/shift/open')->assertRedirect();
        }

        $this->assertNotEmpty($before);
        $this->actingAs($supervisor)->getJson('/supervisor/feed')->assertOk();
    }

    public function test_the_game_master_dashboard_feed_changes_when_a_bet_arrives(): void
    {
        $fight = $this->openFight();
        $gm = User::where('username', 'gm.north')->firstOrFail();
        $teller = User::where('username', 'pos01')->firstOrFail();

        $this->actingAs($gm)->get('/gm')->assertOk()
            ->assertSee('data-live-refresh', false)
            ->assertSee('/gm/feed');

        $before = $this->actingAs($gm)->getJson('/gm/feed')->assertOk()->json('signature');

        if (! $teller->tellerProfile->openShift()) {
            $this->actingAs($teller)->post('/pos/shift/open')->assertRedirect();
        }

        app(BettingService::class)->placeBet($teller, $fight, 'red', 100);

        $after = $this->actingAs($gm)->getJson('/gm/feed')->assertOk()->json('signature');

        $this->assertNotSame($before, $after);
    }

    public function test_the_supervisor_live_floor_feed_changes_when_the_pit_moves(): void
    {
        $fight = $this->openFight();
        $supervisor = User::where('username', 'supervisor')->firstOrFail();
        $gm = User::where('username', 'gm.north')->firstOrFail();

        $this->actingAs($supervisor)->get('/supervisor/live')->assertOk()
            ->assertSee('data-live-refresh', false)
            ->assertSee('/supervisor/live/feed');

        $before = $this->actingAs($supervisor)->getJson('/supervisor/live/feed')->assertOk()->json('signature');

        app(BettingService::class)->lockBetting($fight, $gm);

        $after = $this->actingAs($supervisor)->getJson('/supervisor/live/feed')->assertOk()->json('signature');

        $this->assertNotSame($before, $after);
    }

    public function test_the_supervisor_live_feed_changes_when_the_stop_ratio_changes(): void
    {
        $fight = $this->openFight();
        $supervisor = User::where('username', 'supervisor')->firstOrFail();

        $before = $this->actingAs($supervisor)->getJson('/supervisor/live/feed')->assertOk()->json('signature');

        $fight->update(['stop_ratio' => 2.000]);

        $after = $this->actingAs($supervisor)->getJson('/supervisor/live/feed')->assertOk()->json('signature');

        $this->assertNotSame($before, $after);
    }

    public function test_only_a_game_master_can_read_the_gm_feed(): void
    {
        $this->seed();
        $teller = User::where('username', 'pos01')->firstOrFail();

        $this->actingAs($teller)->getJson('/gm/feed')->assertForbidden();
    }
}
