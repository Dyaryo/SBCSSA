<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Fight;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_card_form_collects_the_paper_settings(): void
    {
        $this->seed();
        $gm = User::where('username', 'gm.north')->firstOrFail();

        $this->actingAs($gm)->post('/gm/events', [
            'type' => 'regular',
            'name' => 'Saturday Card',
            'starts_at' => '2026-11-14',
            'venue' => 'Camsur Sports Arena',
            'stop_ratio' => 1.400,
            'planned_fights' => 500,
            'bomba_amount' => 1000,
            'bomba_breakdown' => 3,
            'bomba_interval' => 30,
            'payout_display' => 'whole',
            'tv_total_bets' => 70,
        ])->assertRedirect()->assertSessionHasNoErrors();

        $event = Event::where('name', 'Saturday Card')->firstOrFail();

        $this->assertSame(500, $event->planned_fights);
        $this->assertSame(1000, $event->bomba_amount);
        $this->assertSame(3, $event->bomba_breakdown);
        $this->assertSame(30, $event->bomba_interval);
        $this->assertSame('whole', $event->payout_display);
        $this->assertSame(70, $event->tv_total_bets);
        $this->assertSame(1.4, (float) $event->stop_ratio);
    }

    public function test_the_event_form_updates_the_card(): void
    {
        $this->seed();
        $gm = User::where('username', 'gm.north')->firstOrFail();

        $this->actingAs($gm)->post('/gm/events', [
            'type' => 'regular',
            'name' => 'Friday Card',
            'starts_at' => '2026-11-13',
        ])->assertRedirect();

        $event = Event::where('name', 'Friday Card')->firstOrFail();

        $this->actingAs($gm)
            ->get('/gm/events/'.$event->id.'/edit')
            ->assertOk()
            ->assertSee('Event form')
            ->assertSee('Bomba break down')
            ->assertSee('TV total bets')
            ->assertSee('Close event');

        $this->actingAs($gm)->patch('/gm/events/'.$event->id, [
            'name' => 'Friday Card — Final',
            'starts_at' => '2026-11-14',
            'status' => 'open',
            'stop_ratio' => 1.500,
            'planned_fights' => 12,
            'bomba_amount' => 250,
            'bomba_breakdown' => 5,
            'bomba_interval' => 45,
            'payout_display' => 'whole',
            'tv_total_bets' => 50,
        ])->assertRedirect(route('gm.events.edit', $event))->assertSessionHasNoErrors();

        $event->refresh();

        $this->assertSame('Friday Card — Final', $event->name);
        $this->assertSame(Event::STATUS_OPEN, $event->status);
        $this->assertSame(12, $event->planned_fights);
        $this->assertSame(250, $event->bomba_amount);
        $this->assertSame(5, $event->bomba_breakdown);
        $this->assertSame(45, $event->bomba_interval);
        $this->assertSame(50, $event->tv_total_bets);
        $this->assertSame('whole', $event->payout_display);
    }

    public function test_changing_the_card_stop_ratio_updates_fights_not_yet_settled(): void
    {
        $this->seed();
        $gm = User::where('username', 'gm.north')->firstOrFail();

        $event = Event::create([
            'code' => 'REG-STOP-01',
            'name' => 'Stop Ratio Card',
            'type' => Event::TYPE_REGULAR,
            'game_master_id' => $gm->gameMasterProfile->id,
            'starts_at' => now(),
            'stop_ratio' => 1.4,
            'status' => Event::STATUS_OPEN,
        ]);

        $open = Fight::create([
            'event_id' => $event->id,
            'fight_no' => 1,
            'odds_red' => 1.850,
            'odds_blue' => 2.050,
            'stop_ratio' => 1.4,
            'status' => Fight::STATUS_BETTING_OPEN,
        ]);

        $settled = Fight::create([
            'event_id' => $event->id,
            'fight_no' => 2,
            'odds_red' => 1.850,
            'odds_blue' => 2.050,
            'stop_ratio' => 1.4,
            'status' => Fight::STATUS_SETTLED,
            'result' => Fight::RESULT_MERON,
        ]);

        $this->actingAs($gm)->patch('/gm/events/'.$event->id, [
            'name' => 'Stop Ratio Card',
            'starts_at' => now()->toDateString(),
            'status' => 'open',
            'stop_ratio' => 2.000,
            'payout_display' => 'decimals',
            'tv_total_bets' => 100,
        ])->assertRedirect(route('gm.events.edit', $event))->assertSessionHasNoErrors();

        $this->assertSame(2.0, (float) $open->fresh()->stop_ratio);
        $this->assertSame(925, $open->fresh()->payoutFor(Fight::SIDE_RED, 500));

        // A settled fight keeps the ratio its tickets were written under.
        $this->assertSame(1.4, (float) $settled->fresh()->stop_ratio);
    }

    public function test_closing_a_card_locks_it(): void
    {
        $this->seed();
        $gm = User::where('username', 'gm.north')->firstOrFail();

        $event = Event::create([
            'code' => 'REG-CL-01',
            'name' => 'To Close',
            'type' => Event::TYPE_REGULAR,
            'game_master_id' => $gm->gameMasterProfile->id,
            'starts_at' => now(),
            'status' => Event::STATUS_OPEN,
        ]);

        $this->actingAs($gm)
            ->post('/gm/events/'.$event->id.'/close')
            ->assertRedirect();

        $this->assertSame(Event::STATUS_CLOSED, $event->fresh()->status);

        $this->actingAs($gm)
            ->from('/gm/events/'.$event->id.'/edit')
            ->patch('/gm/events/'.$event->id, [
                'name' => 'Should Not Change',
                'status' => 'open',
                'payout_display' => 'decimals',
                'tv_total_bets' => 100,
            ])
            ->assertRedirect('/gm/events/'.$event->id.'/edit')
            ->assertSessionHas('error');

        $this->assertSame('To Close', $event->fresh()->name);
    }

    public function test_another_game_master_cannot_open_the_form(): void
    {
        $this->seed();
        $north = User::where('username', 'gm.north')->firstOrFail();
        $south = User::where('username', 'gm.south')->firstOrFail();

        $event = Event::where('game_master_id', $north->gameMasterProfile->id)->firstOrFail();

        $this->actingAs($south)
            ->get('/gm/events/'.$event->id.'/edit')
            ->assertForbidden();
    }

    public function test_the_fight_console_shows_the_card_settings(): void
    {
        $this->seed();
        $gm = User::where('username', 'gm.north')->firstOrFail();

        $event = Event::create([
            'code' => 'REG-TV-01',
            'name' => 'TV Card',
            'type' => Event::TYPE_REGULAR,
            'game_master_id' => $gm->gameMasterProfile->id,
            'starts_at' => now(),
            'stop_ratio' => 1.5,
            'bomba_amount' => 500,
            'bomba_breakdown' => 4,
            'bomba_interval' => 20,
            'payout_display' => 'whole',
            'tv_total_bets' => 70,
            'status' => Event::STATUS_OPEN,
        ]);

        $fight = Fight::create([
            'event_id' => $event->id,
            'fight_no' => 1,
            'odds_red' => 1.850,
            'odds_blue' => 2.050,
            'stop_ratio' => $event->stop_ratio,
            'status' => Fight::STATUS_BETTING_LOCKED,
        ]);

        $this->actingAs($gm)
            ->get('/gm/fights/'.$fight->id)
            ->assertOk()
            ->assertSee('TV total bets')
            ->assertSee('70% of the pool')
            ->assertSee('Bomba');
    }
}
