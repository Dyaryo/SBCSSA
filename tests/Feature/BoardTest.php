<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Fight;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BoardTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_board_is_public_and_read_only(): void
    {
        $this->seed();

        $this->get('/board')
            ->assertOk()
            ->assertSee('Results')
            ->assertSee('MERON')
            ->assertSee('WALA')
            ->assertSee('DRAW')
            ->assertSee('FIGHT #')
            ->assertSee('Minimum bet')
            ->assertSee('Winners road')
            ->assertSee('TV total bets')
            ->assertSee('Play money only')
            ->assertSee('no cash value')
            ->assertSee('no purchase, cash-in or cash payout')
            ->assertSee('tv-display', false)
            ->assertSee('road-cell', false)
            ->assertSee('cr shown')
            ->assertDontSee('Sign out');
    }

    public function test_the_board_shows_the_winner_circles_and_result_counts(): void
    {
        $this->seed();

        $settled = Fight::whereNotNull('result')->get();
        $this->assertGreaterThan(0, $settled->count());

        $response = $this->get('/board')->assertOk();

        $response->assertSee('road-mark '.$settled->first()->result, false);
        $response->assertSee('road-mark', false);
    }

    public function test_the_board_can_be_pointed_at_one_event(): void
    {
        $this->seed();

        $event = Event::whereHas('fights')->firstOrFail();

        $this->get('/board?event_id='.$event->id)
            ->assertOk()
            ->assertSee($event->name);
    }

    public function test_the_board_skips_a_card_with_no_fights(): void
    {
        $this->seed();
        $gm = \App\Models\User::where('username', 'gm.north')->firstOrFail();

        $empty = Event::create([
            'code' => 'REG-EMPTY',
            'name' => 'Empty Card',
            'type' => Event::TYPE_REGULAR,
            'game_master_id' => $gm->gameMasterProfile->id,
            'starts_at' => now()->addDay(),
            'status' => Event::STATUS_OPEN,
        ]);

        $busy = Event::whereHas('fights')->firstOrFail();

        $this->get('/board')
            ->assertOk()
            ->assertSee($busy->name)
            ->assertDontSee($empty->name);
    }

    public function test_the_board_renders_with_no_card_at_all(): void
    {
        $this->get('/board')
            ->assertOk()
            ->assertSee('NO FIGHT')
            ->assertSee('No event open');
    }
}
