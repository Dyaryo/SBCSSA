<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Fight;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StopRatioTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_stop_ratio_caps_the_payout_below_the_posted_odds(): void
    {
        $fight = new Fight(['odds_red' => 2.000, 'odds_blue' => 1.500]);

        $this->assertSame(2.0, $fight->effectiveOddsFor(Fight::SIDE_RED));
        $this->assertSame(200, $fight->payoutFor(Fight::SIDE_RED, 100));

        $fight->stop_ratio = 1.500;

        $this->assertSame(1.5, $fight->effectiveOddsFor(Fight::SIDE_RED));
        $this->assertSame(150, $fight->payoutFor(Fight::SIDE_RED, 100));
        // The blue odds are already below the cap, so they are untouched.
        $this->assertSame(1.5, $fight->effectiveOddsFor(Fight::SIDE_BLUE));
        $this->assertTrue($fight->isStopRatioActive());
        $this->assertTrue($fight->isCappedFor(Fight::SIDE_RED));
        $this->assertFalse($fight->isCappedFor(Fight::SIDE_BLUE));
        $this->assertFalse($fight->isCappedFor(Fight::SIDE_DRAW));
    }

    public function test_a_ratio_above_the_odds_changes_nothing(): void
    {
        $fight = new Fight(['odds_red' => 1.700, 'odds_blue' => 1.900, 'stop_ratio' => 2.500]);

        $this->assertSame(170, $fight->payoutFor(Fight::SIDE_RED, 100));
        $this->assertSame(190, $fight->payoutFor(Fight::SIDE_BLUE, 100));
    }

    public function test_a_fight_without_a_stop_ratio_pays_the_full_odds(): void
    {
        $fight = new Fight(['odds_red' => 2.050, 'odds_blue' => 1.850]);

        $this->assertFalse($fight->isStopRatioActive());
        $this->assertSame(205, $fight->payoutFor(Fight::SIDE_RED, 100));
        $this->assertSame(1.85, $fight->effectiveOddsFor(Fight::SIDE_BLUE));
    }

    public function test_a_fight_inherits_the_event_stop_bet_ratio(): void
    {
        $this->seed();
        $gm = User::where('username', 'gm.north')->firstOrFail();
        $event = $this->freshEvent($gm->gameMasterProfile->id, 'EVT-SR-01', 1.500);

        $this->actingAs($gm)->post('/gm/fights', [
            'event_id' => $event->id,
            'fight_no' => 1,
            'odds_red' => 2.000,
            'odds_blue' => 1.800,
        ])->assertRedirect()->assertSessionHasNoErrors();

        $fight = Fight::where('event_id', $event->id)->where('fight_no', 1)->firstOrFail();

        $this->assertSame(1.5, (float) $fight->stop_ratio);
        $this->assertSame(150, $fight->payoutFor(Fight::SIDE_RED, 100));
    }

    public function test_the_ratio_is_not_asked_for_on_the_fight_form(): void
    {
        $this->seed();

        $this->actingAs(User::where('username', 'gm.north')->firstOrFail())
            ->get('/gm/fights')
            ->assertOk()
            ->assertDontSee('name="stop_ratio"', false)
            ->assertSee('applies to every fight');
    }

    public function test_an_out_of_range_stop_ratio_is_rejected_at_the_event(): void
    {
        $this->seed();
        $gm = User::where('username', 'gm.north')->firstOrFail();

        $this->actingAs($gm)
            ->from('/gm/events')
            ->post('/gm/events', [
                'type' => 'regular',
                'name' => 'Bad Ratio Night',
                'starts_at' => '2026-11-11',
                'stop_ratio' => 0.500,
                'venue' => 'Bad Ratio Arena',
            ])
            ->assertRedirect('/gm/events')
            ->assertSessionHasErrors('stop_ratio');

        $this->assertDatabaseMissing('events', ['venue' => 'Bad Ratio Arena']);
    }

    public function test_the_event_keeps_the_ratio_it_was_created_with(): void
    {
        $this->seed();
        $gm = User::where('username', 'gm.north')->firstOrFail();

        $this->actingAs($gm)->post('/gm/events', [
            'type' => 'regular',
            'name' => 'Capped Night',
            'starts_at' => '2026-11-11',
            'stop_ratio' => 1.400,
            'venue' => 'Capped Arena',
        ])->assertRedirect()->assertSessionHasNoErrors();

        $event = Event::where('venue', 'Capped Arena')->firstOrFail();

        $this->assertTrue($event->hasStopRatio());
        $this->assertSame(1.4, (float) $event->stop_ratio);
    }

    private function freshEvent(int $gameMasterId, string $code, ?float $stopRatio = null): Event
    {
        return Event::create([
            'code' => $code,
            'name' => null,
            'type' => Event::TYPE_REGULAR,
            'game_master_id' => $gameMasterId,
            'starts_at' => now(),
            'stop_ratio' => $stopRatio,
            'status' => Event::STATUS_OPEN,
        ]);
    }
}
