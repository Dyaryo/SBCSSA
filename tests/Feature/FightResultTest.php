<?php

namespace Tests\Feature;

use App\Models\Bet;
use App\Models\Cock;
use App\Models\Event;
use App\Models\Fight;
use App\Models\GameMasterProfile;
use App\Models\ShiftSession;
use App\Models\TellerProfile;
use App\Models\User;
use App\Services\BettingService;
use App\Services\ShiftSlipService;
use App\Services\WalletService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FightResultTest extends TestCase
{
    use RefreshDatabase;

    private function makeFight(): array
    {
        $gmUser = User::create([
            'name' => 'GM Grading',
            'username' => 'gm.grading',
            'password' => 'Password@123',
            'role' => User::ROLE_GAME_MASTER,
            'status' => User::STATUS_ACTIVE,
        ]);

        $gm = GameMasterProfile::create(['user_id' => $gmUser->id]);

        $event = Event::create([
            'code' => 'EVT-G1',
            'name' => 'Grading Derby',
            'game_master_id' => $gm->id,
            'status' => Event::STATUS_OPEN,
        ]);

        $red = Cock::create(['name' => 'Meron Bird', 'status' => 'available']);
        $blue = Cock::create(['name' => 'Wala Bird', 'status' => 'available']);

        $fight = Fight::create([
            'event_id' => $event->id,
            'fight_no' => 1,
            'red_cock_id' => $red->id,
            'blue_cock_id' => $blue->id,
            'odds_red' => 2.000,
            'odds_blue' => 1.800,
            'status' => Fight::STATUS_SCHEDULED,
        ]);

        return [$gmUser, $gm, $fight, $red, $blue];
    }

    private function makeTeller(GameMasterProfile $gm, int $credits = 1000): array
    {
        $user = User::create([
            'name' => 'Teller Grading',
            'username' => 'teller.grading',
            'password' => 'Password@123',
            'role' => User::ROLE_TELLER,
            'status' => User::STATUS_ACTIVE,
        ]);

        $teller = TellerProfile::create([
            'user_id' => $user->id,
            'teller_code' => 'POS-G1',
            'game_master_id' => $gm->id,
            'status' => 'active',
        ]);

        $user->walletOrCreate();
        app(WalletService::class)->seedCredits($user, $credits, null, 'test allocation');

        ShiftSession::create([
            'teller_id' => $teller->id,
            'opened_at' => now(),
            'status' => ShiftSession::STATUS_OPEN,
        ]);

        return [$user, $teller];
    }

    public function test_a_meron_result_pays_the_red_side(): void
    {
        [$gmUser, $gm, $fight] = $this->makeFight();
        [$tellerUser] = $this->makeTeller($gm, 1000);

        $betting = app(BettingService::class);
        $betting->openBetting($fight, $gmUser);
        $bet = $betting->placeBet($tellerUser, $fight, Fight::SIDE_RED, 100);
        $betting->lockBetting($fight, $gmUser);
        $betting->declareResult($fight, Fight::RESULT_MERON, $gmUser);

        // 1000 - 100 stake + 200 payout at odds 2.000
        $this->assertSame(1100, $tellerUser->fresh()->creditBalance());
        $this->assertSame(Bet::STATUS_WON, $bet->fresh()->status);
        $this->assertSame(Fight::STATUS_SETTLED, $fight->fresh()->status);
        $this->assertSame(Fight::RESULT_MERON, $fight->fresh()->result);
        $this->assertSame('red', $fight->fresh()->winner);
    }

    public function test_a_wala_result_pays_the_blue_side_and_loses_red(): void
    {
        [$gmUser, $gm, $fight] = $this->makeFight();
        [$tellerUser] = $this->makeTeller($gm, 1000);

        $betting = app(BettingService::class);
        $betting->openBetting($fight, $gmUser);
        $redBet = $betting->placeBet($tellerUser, $fight, Fight::SIDE_RED, 200);
        $blueBet = $betting->placeBet($tellerUser, $fight, Fight::SIDE_BLUE, 100);
        $betting->lockBetting($fight, $gmUser);
        $betting->declareResult($fight, Fight::RESULT_WALA, $gmUser);

        // 1000 - 300 staked + 180 payout (100 × 1.800)
        $this->assertSame(880, $tellerUser->fresh()->creditBalance());
        $this->assertSame(Bet::STATUS_LOST, $redBet->fresh()->status);
        $this->assertSame(Bet::STATUS_WON, $blueBet->fresh()->status);
        $this->assertSame(Fight::RESULT_WALA, $fight->fresh()->result);
    }

    public function test_a_draw_pays_the_draw_side_seven_times_and_loses_the_rest(): void
    {
        [$gmUser, $gm, $fight] = $this->makeFight();
        [$tellerUser] = $this->makeTeller($gm, 1000);

        $betting = app(BettingService::class);
        $betting->openBetting($fight, $gmUser);
        $redBet = $betting->placeBet($tellerUser, $fight, Fight::SIDE_RED, 100);
        $drawBet = $betting->placeBet($tellerUser, $fight, Fight::SIDE_DRAW, 100);
        $betting->lockBetting($fight, $gmUser);
        $betting->declareResult($fight, Fight::RESULT_DRAW, $gmUser);

        // 1000 - 200 staked + 700 draw payout (100 × the card's default 7)
        $this->assertSame(1500, $tellerUser->fresh()->creditBalance());
        $this->assertSame(Bet::STATUS_LOST, $redBet->fresh()->status);
        $this->assertSame(Bet::STATUS_WON, $drawBet->fresh()->status);
        $this->assertSame(700, (int) $drawBet->fresh()->potential_payout);
        $this->assertSame(Fight::STATUS_SETTLED, $fight->fresh()->status);
        $this->assertSame(Fight::RESULT_DRAW, $fight->fresh()->result);
        $this->assertTrue($fight->fresh()->isDraw());
    }

    public function test_a_cancelled_fight_refunds_stakes_and_records_the_reason(): void
    {
        [$gmUser, $gm, $fight] = $this->makeFight();
        [$tellerUser] = $this->makeTeller($gm, 1000);

        $betting = app(BettingService::class);
        $betting->openBetting($fight, $gmUser);
        $bet = $betting->placeBet($tellerUser, $fight, Fight::SIDE_RED, 400);
        $betting->lockBetting($fight, $gmUser);
        $betting->declareResult($fight, Fight::RESULT_CANCELLED, $gmUser, 'referee called it off');

        $this->assertSame(1000, $tellerUser->fresh()->creditBalance());
        $this->assertSame(Bet::STATUS_REFUNDED, $bet->fresh()->status);
        $this->assertSame(Fight::STATUS_CANCELLED, $fight->fresh()->status);
        $this->assertSame(Fight::RESULT_CANCELLED, $fight->fresh()->result);
        $this->assertSame('referee called it off', $fight->fresh()->void_reason);
    }

    public function test_a_game_master_grades_from_the_console_and_creates_the_next_fight(): void
    {
        [$gmUser, $gm, $fight, $red, $blue] = $this->makeFight();
        [$tellerUser] = $this->makeTeller($gm, 1000);

        $betting = app(BettingService::class);
        $betting->openBetting($fight, $gmUser);
        $betting->placeBet($tellerUser, $fight, Fight::SIDE_RED, 100);
        $betting->lockBetting($fight, $gmUser);

        $this->actingAs($gmUser)
            ->post('/gm/fights/'.$fight->id.'/result', ['result' => 'meron'])
            ->assertRedirect();

        $this->assertSame(Fight::RESULT_MERON, $fight->fresh()->result);

        $response = $this->actingAs($gmUser)->post('/gm/fights/'.$fight->id.'/next', [
            'red_cock_id' => $red->id,
            'blue_cock_id' => $blue->id,
            'odds_red' => 1.750,
            'odds_blue' => 2.100,
        ]);

        $next = Fight::where('event_id', $fight->event_id)->where('fight_no', 2)->firstOrFail();

        $response->assertRedirect(route('gm.fights.show', $next));
        $this->assertSame(Fight::STATUS_SCHEDULED, $next->status);
        $this->assertEquals(1.750, (float) $next->odds_red);
    }

    public function test_cancelling_requires_a_reason(): void
    {
        [$gmUser, $gm, $fight] = $this->makeFight();

        $this->actingAs($gmUser)->post('/gm/fights/'.$fight->id.'/result', ['result' => 'cancelled'])
            ->assertSessionHasErrors('reason');

        $this->assertSame(Fight::STATUS_SCHEDULED, $fight->fresh()->status);
    }

    public function test_the_printable_ticket_carries_a_scannable_code(): void
    {
        $this->seed();

        $user = User::where('username', 'pos01')->firstOrFail();
        $bet = Bet::whereHas('teller', fn ($q) => $q->where('teller_code', 'POS 001'))->firstOrFail();

        $code = app(ShiftSlipService::class)->code('BET', $bet->id, (int) $bet->amount_credits, (string) $bet->placed_at);

        $this->actingAs($user)
            ->get('/pos/bets/'.$bet->id.'/receipt')
            ->assertOk()
            ->assertSee('<svg', false)
            ->assertSee($code);
    }

    public function test_a_teller_can_look_a_slip_up_and_a_tampered_code_is_rejected(): void
    {
        $this->seed();

        $user = User::where('username', 'pos01')->firstOrFail();
        $bet = Bet::whereHas('teller', fn ($q) => $q->where('teller_code', 'POS 001'))->firstOrFail();

        $code = app(ShiftSlipService::class)->code('BET', $bet->id, (int) $bet->amount_credits, (string) $bet->placed_at);

        $this->actingAs($user)
            ->get('/pos/lookup?code='.$code)
            ->assertOk()
            ->assertSee('Bet #'.$bet->id);

        $this->actingAs($user)
            ->get('/pos/lookup?code=BET-'.str_pad((string) $bet->id, 6, '0', STR_PAD_LEFT).'-AAAAAAAA')
            ->assertOk()
            ->assertSee('does not match the record');
    }

    public function test_a_teller_cannot_look_up_another_terminal_slip(): void
    {
        $this->seed();

        $user = User::where('username', 'pos01')->firstOrFail();
        $foreign = Bet::whereHas('teller', fn ($q) => $q->where('teller_code', 'POS 002'))->firstOrFail();

        $code = app(ShiftSlipService::class)->code('BET', $foreign->id, (int) $foreign->amount_credits, (string) $foreign->placed_at);

        $this->actingAs($user)
            ->get('/pos/lookup?code='.$code)
            ->assertOk()
            ->assertSee('issued by');
    }
}
