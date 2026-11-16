<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Fight;
use App\Models\User;
use App\Services\BettingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BoardBigBetTest extends TestCase
{
    use RefreshDatabase;

    private function openFight(User $gm, User $teller, int $amount): Fight
    {
        $event = Event::create([
            'code' => 'REG-BIG',
            'name' => 'Big Bet Card',
            'type' => Event::TYPE_REGULAR,
            'game_master_id' => $gm->gameMasterProfile->id,
            'starts_at' => now(),
            'status' => Event::STATUS_OPEN,
        ]);

        $fight = Fight::create([
            'event_id' => $event->id,
            'fight_no' => 77,
            'odds_red' => 1.850,
            'odds_blue' => 2.050,
            'status' => Fight::STATUS_BETTING_OPEN,
            'betting_opened_at' => now(),
        ]);

        app(BettingService::class)->placeBet($teller, $fight, Fight::SIDE_RED, $amount);

        return $fight;
    }

    public function test_a_big_slip_flashes_the_board_side_without_slip_details(): void
    {
        $this->seed();

        $north = User::where('username', 'gm.north')->firstOrFail();
        $teller = User::where('username', 'pos01')->firstOrFail();

        $fight = $this->openFight($north, $teller, (int) config('pos.big_bet_threshold'));

        $feed = $this->getJson('/board/feed?event_id='.$fight->event_id)->assertOk();
        $feed->assertJsonPath('side', Fight::SIDE_RED);

        $payload = $feed->json();
        $this->assertSame(['token', 'side', 'signature'], array_keys($payload));
        $this->assertMatchesRegularExpression('/^[a-f0-9]{24}$/', $payload['token']);
        $this->assertMatchesRegularExpression('/^[a-f0-9]{32}$/', $payload['signature']);
    }

    public function test_a_small_slip_does_not_flash_the_board(): void
    {
        $this->seed();

        $north = User::where('username', 'gm.north')->firstOrFail();
        $teller = User::where('username', 'pos01')->firstOrFail();

        $fight = $this->openFight($north, $teller, 500);

        $this->getJson('/board/feed?event_id='.$fight->event_id)
            ->assertOk()
            ->assertJsonPath('token', null)
            ->assertJsonPath('side', null)
            ->assertJsonStructure(['token', 'side', 'signature']);
    }

    public function test_the_public_board_wires_the_flash_without_slip_values(): void
    {
        $this->seed();

        $north = User::where('username', 'gm.north')->firstOrFail();
        $teller = User::where('username', 'pos01')->firstOrFail();

        $fight = $this->openFight($north, $teller, (int) config('pos.big_bet_threshold'));

        $response = $this->get('/board?event_id='.$fight->event_id)
            ->assertOk()
            ->assertSee('data-big-bet-feed', false)
            ->assertSee('data-big-bet-token', false)
            ->assertSee('data-board-signature', false)
            ->assertSee('css/board.css', false)
            ->assertSee('js/board.js', false);

        $this->assertStringContainsString('BIG BET', (string) file_get_contents(public_path('css/board.css')));

        $this->assertStringNotContainsString('BET-000001', $response->getContent());
        $this->assertStringNotContainsString('MANUAL CODE', $response->getContent());
    }

    public function test_locking_a_side_changes_the_board_signature(): void
    {
        $this->seed();

        $north = User::where('username', 'gm.north')->firstOrFail();
        $teller = User::where('username', 'pos01')->firstOrFail();
        $fight = $this->openFight($north, $teller, 500);

        $before = $this->getJson('/board/feed?event_id='.$fight->event_id)
            ->assertOk()
            ->json('signature');

        $fight->update(['red_locked' => true]);

        $after = $this->getJson('/board/feed?event_id='.$fight->event_id)
            ->assertOk()
            ->json('signature');

        $this->assertNotSame($before, $after);

        $this->get('/board?event_id='.$fight->event_id)
            ->assertOk()
            ->assertSee('MERON LOCKED');
    }
}
