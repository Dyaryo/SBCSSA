<?php

namespace Tests\Feature;

use App\Models\Bet;
use App\Models\Event;
use App\Models\Fight;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PosLiveFeedTest extends TestCase
{
    use RefreshDatabase;

    private function ownEvent(User $gmUser, ?float $stopRatio = null): Event
    {
        return Event::create([
            'code' => 'REG-FEED01',
            'name' => 'Feed Test Card',
            'type' => Event::TYPE_REGULAR,
            'game_master_id' => $gmUser->gameMasterProfile->id,
            'starts_at' => now(),
            'stop_ratio' => $stopRatio,
            'status' => Event::STATUS_OPEN,
        ]);
    }

    public function test_the_pos_labels_posted_odds_and_the_capped_payout_rate(): void
    {
        $this->seed();

        $north = User::where('username', 'gm.north')->firstOrFail();
        $teller = User::where('username', 'pos01')->firstOrFail();

        Fight::create([
            'event_id' => $this->ownEvent($north, 2.000)->id,
            'fight_no' => 4,
            'odds_red' => 1.850,
            'odds_blue' => 2.050,
            'stop_ratio' => 2.000,
            'status' => Fight::STATUS_BETTING_OPEN,
            'betting_opened_at' => now(),
        ]);

        $this->actingAs($teller)
            ->get('/pos')
            ->assertOk()
            ->assertSee('posted odds')
            ->assertSee('PAYS 1.850×')
            ->assertSee('PAYS 2.000× · capped')
            ->assertSee('Bet stopper 2.000× caps this fight.')
            ->assertSee('Wala posted 2.050×')
            ->assertSee('pays 2.000×.');
    }

    public function test_the_quick_amounts_go_up_to_one_hundred_thousand(): void
    {
        $this->seed();
        $teller = User::where('username', 'pos01')->firstOrFail();

        $this->actingAs($teller)
            ->get('/pos')
            ->assertOk()
            ->assertSee('data-amount="30000"', false)
            ->assertSee('data-amount="50000"', false)
            ->assertSee('data-amount="90000"', false)
            ->assertSee('data-amount="100000"', false)
            ->assertSee('max="100000"', false)
            ->assertSee('data-pos-live', false)
            ->assertSee('data-pos-fight-stack', false)
            ->assertSee('data-window-url', false);
    }

    public function test_a_one_hundred_thousand_credit_bet_is_accepted(): void
    {
        $this->seed();

        $north = User::where('username', 'gm.north')->firstOrFail();
        $teller = User::where('username', 'pos01')->firstOrFail();

        $fight = Fight::create([
            'event_id' => $this->ownEvent($north)->id,
            'fight_no' => 1,
            'odds_red' => 1.850,
            'odds_blue' => 2.050,
            'status' => Fight::STATUS_BETTING_OPEN,
        ]);

        if (! $teller->tellerProfile->openShift()) {
            $this->actingAs($teller)->post('/pos/shift/open')->assertRedirect();
        }

        $this->actingAs($teller)->get('/pos')->assertOk();

        $this->actingAs($teller)
            ->post('/pos/bets', ['fight_id' => $fight->id, 'side' => 'red', 'amount' => 100000])
            ->assertRedirect()
            ->assertSessionHas('status');

        $this->assertSame(100000, (int) Bet::where('fight_id', $fight->id)->sum('amount_credits'));
    }

    public function test_the_feed_signature_changes_when_the_stop_ratio_changes(): void
    {
        $this->seed();

        $north = User::where('username', 'gm.north')->firstOrFail();
        $teller = User::where('username', 'pos01')->firstOrFail();

        $fight = Fight::create([
            'event_id' => $this->ownEvent($north)->id,
            'fight_no' => 5,
            'odds_red' => 1.850,
            'odds_blue' => 2.050,
            'status' => Fight::STATUS_BETTING_OPEN,
            'betting_opened_at' => now(),
        ]);

        $before = $this->actingAs($teller)->getJson('/pos/fights-feed')->assertOk()->json('signature');

        $fight->update(['stop_ratio' => 2.000]);

        $after = $this->actingAs($teller)->getJson('/pos/fights-feed')->assertOk()->json('signature');
        $this->assertNotSame($before, $after);
    }

    public function test_the_feed_signature_changes_when_the_game_master_opens_a_fight(): void
    {
        $this->seed();

        $north = User::where('username', 'gm.north')->firstOrFail();
        $teller = User::where('username', 'pos01')->firstOrFail();

        $before = $this->actingAs($teller)->getJson('/pos/fights-feed')->assertOk()->json('signature');

        Fight::create([
            'event_id' => $this->ownEvent($north)->id,
            'fight_no' => 9,
            'odds_red' => 1.800,
            'odds_blue' => 2.000,
            'status' => Fight::STATUS_BETTING_OPEN,
            'betting_opened_at' => now(),
        ]);

        $afterOpen = $this->actingAs($teller)->getJson('/pos/fights-feed')->assertOk()->json('signature');
        $this->assertNotSame($before, $afterOpen);

        Fight::query()->update(['status' => Fight::STATUS_SETTLED, 'result' => Fight::RESULT_MERON]);

        $afterClose = $this->actingAs($teller)->getJson('/pos/fights-feed')->assertOk()->json('signature');
        $this->assertNotSame($afterOpen, $afterClose);
    }

    public function test_the_window_partial_returns_the_sliding_cards(): void
    {
        $this->seed();

        $north = User::where('username', 'gm.north')->firstOrFail();
        $teller = User::where('username', 'pos01')->firstOrFail();

        Fight::create([
            'event_id' => $this->ownEvent($north)->id,
            'fight_no' => 12,
            'odds_red' => 1.800,
            'odds_blue' => 2.000,
            'status' => Fight::STATUS_BETTING_OPEN,
            'betting_opened_at' => now(),
        ]);

        $window = $this->actingAs($teller)->getJson('/pos/fights-window')->assertOk();
        $window->assertJsonStructure(['signature', 'fight_signature', 'shift_id', 'html']);
        $this->assertStringContainsString('Fight 12', $window->json('html'));
        $this->assertStringContainsString('pos-fight', $window->json('html'));
    }

    public function test_only_a_teller_can_read_the_window_partial(): void
    {
        $this->seed();
        $supervisor = User::where('username', 'supervisor')->firstOrFail();

        $this->actingAs($supervisor)->getJson('/pos/fights-window')->assertForbidden();
    }

    public function test_only_a_teller_can_read_the_window_feed(): void
    {
        $this->seed();
        $supervisor = User::where('username', 'supervisor')->firstOrFail();

        $this->actingAs($supervisor)->getJson('/pos/fights-feed')->assertForbidden();
    }
}
