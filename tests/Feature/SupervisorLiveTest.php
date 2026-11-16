<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Fight;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SupervisorLiveTest extends TestCase
{
    use RefreshDatabase;

    private function runningFight(bool $meronLocked = false): Fight
    {
        $this->seed();

        $north = User::where('username', 'gm.north')->firstOrFail();

        $event = Event::create([
            'code' => 'REG-LIVE01',
            'name' => 'Live Floor Card',
            'type' => Event::TYPE_REGULAR,
            'game_master_id' => $north->gameMasterProfile->id,
            'starts_at' => now(),
            'status' => Event::STATUS_OPEN,
            'planned_fights' => 10,
            'stop_ratio' => 1.4,
            'draw_ratio' => 7,
            'tv_total_bets' => 70,
        ]);

        return Fight::create([
            'event_id' => $event->id,
            'fight_no' => 4,
            'odds_red' => 1.850,
            'odds_blue' => 2.050,
            'status' => Fight::STATUS_BETTING_OPEN,
            'betting_opened_at' => now(),
            'red_locked' => $meronLocked,
        ]);
    }

    public function test_the_live_floor_shows_the_card_the_open_window_and_the_road(): void
    {
        $fight = $this->runningFight(meronLocked: true);
        $supervisor = User::where('username', 'supervisor')->firstOrFail();

        $this->actingAs($supervisor)
            ->get('/supervisor/live')
            ->assertOk()
            ->assertSee('Live floor')
            ->assertSee('Open betting windows')
            ->assertSee('Live Floor Card')
            ->assertSee('#4')
            ->assertSee('1.850×')
            ->assertSee('MERON LOCKED')
            ->assertSee('Winners road')
            ->assertSee('road-cell', false)
            ->assertSee('Current cards')
            ->assertSee('Locked sides');
    }

    public function test_the_live_floor_shows_posted_and_capped_payout_rates(): void
    {
        $fight = $this->runningFight();
        $fight->update(['stop_ratio' => 2.000]);
        $supervisor = User::where('username', 'supervisor')->firstOrFail();

        $this->actingAs($supervisor)
            ->get('/supervisor/live')
            ->assertOk()
            ->assertSee('posted 1.850×')
            ->assertSee('PAYS 1.850×')
            ->assertSee('posted 2.050×')
            ->assertSee('PAYS 2.000× · capped');
    }

    public function test_the_live_floor_is_linked_from_the_supervisor_navigation(): void
    {
        $this->seed();
        $supervisor = User::where('username', 'supervisor')->firstOrFail();

        $this->actingAs($supervisor)
            ->get('/supervisor')
            ->assertOk()
            ->assertSee('Live floor')
            ->assertSee('/supervisor/live');
    }

    public function test_the_live_floor_says_so_when_no_window_is_open(): void
    {
        $this->seed();
        Fight::query()->update(['status' => Fight::STATUS_SETTLED]);
        Event::query()->update(['status' => Event::STATUS_CLOSED]);

        $supervisor = User::where('username', 'supervisor')->firstOrFail();

        $this->actingAs($supervisor)
            ->get('/supervisor/live')
            ->assertOk()
            ->assertSee('No window is open right now')
            ->assertSee('No card is running.');
    }
}
