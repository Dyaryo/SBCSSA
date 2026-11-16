<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Fight;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FightScheduleTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_fight_is_scheduled_with_meron_and_wala_only(): void
    {
        $this->seed();
        $gm = User::where('username', 'gm.north')->firstOrFail();
        $event = $this->freshEvent($gm->gameMasterProfile->id, 'EVT-SCH-01');

        $this->actingAs($gm)->post('/gm/fights', [
            'event_id' => $event->id,
            'fight_no' => 1,
            'odds_red' => 1.850,
            'odds_blue' => 2.050,
        ])->assertRedirect()->assertSessionHasNoErrors();

        $fight = Fight::where('event_id', $event->id)->where('fight_no', 1)->firstOrFail();

        // No fighter roster is involved: the sides are simply Meron and Wala.
        $this->assertNull($fight->red_cock_id);
        $this->assertNull($fight->blue_cock_id);
    }

    public function test_the_next_fight_cannot_be_created_before_a_winner_is_declared(): void
    {
        $this->seed();
        $gm = User::where('username', 'gm.north')->firstOrFail();
        $event = $this->freshEvent($gm->gameMasterProfile->id, 'EVT-SCH-02');

        $first = Fight::create([
            'event_id' => $event->id,
            'fight_no' => 1,
            'red_cock_id' => \App\Models\Cock::orderBy('id')->firstOrFail()->id,
            'blue_cock_id' => \App\Models\Cock::orderByDesc('id')->firstOrFail()->id,
            'odds_red' => 1.850,
            'odds_blue' => 2.050,
            'status' => Fight::STATUS_BETTING_OPEN,
        ]);

        $this->actingAs($gm)
            ->from('/gm/fights')
            ->post('/gm/fights', [
                'event_id' => $event->id,
                'fight_no' => 2,
                'odds_red' => 1.850,
                'odds_blue' => 2.050,
            ])
            ->assertRedirect('/gm/fights')
            ->assertSessionHasErrors('fight_no');

        $this->assertDatabaseMissing('fights', ['event_id' => $event->id, 'fight_no' => 2]);

        // Declare the winning side, then fight #2 is allowed.
        $first->update([
            'status' => Fight::STATUS_SETTLED,
            'result' => 'meron',
            'winner' => 'red',
            'declared_by' => $gm->id,
            'declared_at' => now(),
        ]);

        $this->actingAs($gm)
            ->post('/gm/fights', [
                'event_id' => $event->id,
                'fight_no' => 2,
                'odds_red' => 1.850,
                'odds_blue' => 2.050,
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $second = Fight::where('event_id', $event->id)->where('fight_no', 2)->firstOrFail();

        // Nothing is carried over but the event: every fight is Meron vs Wala.
        $this->assertNull($second->red_cock_id);
        $this->assertNull($second->blue_cock_id);
    }

    public function test_the_schedule_form_no_longer_asks_for_fighters(): void
    {
        $this->seed();

        $this->actingAs(User::where('username', 'gm.north')->firstOrFail())
            ->get('/gm/fights')
            ->assertOk()
            ->assertDontSee('name="red_cock_id"', false)
            ->assertDontSee('name="blue_cock_id"', false)
            ->assertDontSee('Add a fighter')
            ->assertDontSee('fighters registered')
            ->assertSee('Meron odds')
            ->assertSee('Wala odds')
            ->assertDontSee('Red odds')
            ->assertDontSee('name="stop_ratio"', false)
            ->assertSee('Schedule a fight');
    }

    private function freshEvent(int $gameMasterId, string $code): Event
    {
        return Event::create([
            'code' => $code,
            'name' => null,
            'type' => Event::TYPE_REGULAR,
            'game_master_id' => $gameMasterId,
            'starts_at' => now(),
            'status' => Event::STATUS_OPEN,
        ]);
    }
}
