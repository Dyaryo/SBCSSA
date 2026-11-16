<?php

namespace Tests\Feature;

use App\Exceptions\BettingException;
use App\Models\Bet;
use App\Models\Cock;
use App\Models\Event;
use App\Models\Fight;
use App\Models\GameMasterProfile;
use App\Models\ShiftSession;
use App\Models\TellerProfile;
use App\Models\User;
use App\Services\BettingService;
use App\Services\WalletService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BettingFlowTest extends TestCase
{
    use RefreshDatabase;

    private function makeFight(): array
    {
        $gmUser = User::create([
            'name' => 'GM Test',
            'username' => 'gm.test',
            'password' => 'Password@123',
            'role' => User::ROLE_GAME_MASTER,
            'status' => User::STATUS_ACTIVE,
        ]);

        $gm = GameMasterProfile::create(['user_id' => $gmUser->id]);

        $event = Event::create([
            'code' => 'EVT-T1',
            'name' => 'Test Derby',
            'game_master_id' => $gm->id,
            'status' => Event::STATUS_OPEN,
        ]);

        $red = Cock::create(['name' => 'Red One', 'status' => 'available']);
        $blue = Cock::create(['name' => 'Blue One', 'status' => 'available']);

        $fight = Fight::create([
            'event_id' => $event->id,
            'fight_no' => 1,
            'red_cock_id' => $red->id,
            'blue_cock_id' => $blue->id,
            'odds_red' => 2.000,
            'odds_blue' => 1.800,
            'status' => Fight::STATUS_SCHEDULED,
        ]);

        return [$gmUser, $gm, $fight];
    }

    private function makeTeller(GameMasterProfile $gm, int $credits = 1000): array
    {
        $user = User::create([
            'name' => 'Teller Test',
            'username' => 'teller.test',
            'password' => 'Password@123',
            'role' => User::ROLE_TELLER,
            'status' => User::STATUS_ACTIVE,
        ]);

        $teller = TellerProfile::create([
            'user_id' => $user->id,
            'teller_code' => 'POS-T1',
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

    public function test_a_winning_bet_is_held_then_paid_out(): void
    {
        [$gmUser, $gm, $fight] = $this->makeFight();
        [$tellerUser] = $this->makeTeller($gm, 1000);

        $betting = app(BettingService::class);

        $betting->openBetting($fight, $gmUser);
        $bet = $betting->placeBet($tellerUser, $fight, Fight::SIDE_RED, 100);

        $this->assertSame(900, $tellerUser->fresh()->creditBalance());
        $this->assertSame(Bet::STATUS_ACTIVE, $bet->status);

        $betting->lockBetting($fight, $gmUser);
        $betting->settle($fight, Fight::SIDE_RED, $gmUser);

        // 1000 - 100 stake + 200 payout (odds 2.000)
        $this->assertSame(1100, $tellerUser->fresh()->creditBalance());
        $this->assertSame(Bet::STATUS_WON, $bet->fresh()->status);
        $this->assertSame(Fight::STATUS_SETTLED, $fight->fresh()->status);
    }

    public function test_a_losing_bet_is_not_refunded(): void
    {
        [$gmUser, $gm, $fight] = $this->makeFight();
        [$tellerUser] = $this->makeTeller($gm, 1000);

        $betting = app(BettingService::class);

        $betting->openBetting($fight, $gmUser);
        $bet = $betting->placeBet($tellerUser, $fight, Fight::SIDE_BLUE, 250);
        $betting->lockBetting($fight, $gmUser);
        $betting->settle($fight, Fight::SIDE_RED, $gmUser);

        $this->assertSame(750, $tellerUser->fresh()->creditBalance());
        $this->assertSame(Bet::STATUS_LOST, $bet->fresh()->status);
    }

    public function test_bets_are_rejected_once_betting_is_locked(): void
    {
        [$gmUser, $gm, $fight] = $this->makeFight();
        [$tellerUser] = $this->makeTeller($gm, 1000);

        $betting = app(BettingService::class);
        $betting->openBetting($fight, $gmUser);
        $betting->lockBetting($fight, $gmUser);

        $this->expectException(BettingException::class);

        try {
            $betting->placeBet($tellerUser, $fight, Fight::SIDE_RED, 100);
        } finally {
            $this->assertSame(1000, $tellerUser->fresh()->creditBalance());
        }
    }

    public function test_a_bet_cannot_exceed_the_wallet_balance(): void
    {
        [$gmUser, $gm, $fight] = $this->makeFight();
        [$tellerUser] = $this->makeTeller($gm, 100);

        $betting = app(BettingService::class);
        $betting->openBetting($fight, $gmUser);

        $this->expectException(BettingException::class);

        try {
            $betting->placeBet($tellerUser, $fight, Fight::SIDE_RED, 5000);
        } finally {
            $this->assertSame(100, $tellerUser->fresh()->creditBalance());
        }
    }

    public function test_voiding_a_fight_refunds_every_stake(): void
    {
        [$gmUser, $gm, $fight] = $this->makeFight();
        [$tellerUser] = $this->makeTeller($gm, 1000);

        $betting = app(BettingService::class);
        $betting->openBetting($fight, $gmUser);
        $bet = $betting->placeBet($tellerUser, $fight, Fight::SIDE_RED, 400);
        $betting->lockBetting($fight, $gmUser);
        $betting->voidFight($fight, 'injury before start', $gmUser);

        $this->assertSame(1000, $tellerUser->fresh()->creditBalance());
        $this->assertSame(Bet::STATUS_REFUNDED, $bet->fresh()->status);
        $this->assertSame(Fight::STATUS_VOID, $fight->fresh()->status);
    }

    public function test_the_ledger_matches_every_wallet_after_a_full_cycle(): void
    {
        [$gmUser, $gm, $fight] = $this->makeFight();
        [$tellerUser] = $this->makeTeller($gm, 5000);

        $betting = app(BettingService::class);
        $betting->openBetting($fight, $gmUser);
        $betting->placeBet($tellerUser, $fight, Fight::SIDE_RED, 500);
        $betting->placeBet($tellerUser, $fight, Fight::SIDE_BLUE, 700);
        $betting->lockBetting($fight, $gmUser);
        $betting->settle($fight, Fight::SIDE_BLUE, $gmUser);

        $result = app(WalletService::class)->verify();

        $this->assertSame([], $result['issues']);
        $this->assertGreaterThan(0, $result['checked']);
    }

    public function test_an_idempotency_key_prevents_a_double_charge(): void
    {
        [$gmUser, $gm, $fight] = $this->makeFight();
        [$tellerUser] = $this->makeTeller($gm, 1000);

        $betting = app(BettingService::class);
        $betting->openBetting($fight, $gmUser);

        $first = $betting->placeBet($tellerUser, $fight, Fight::SIDE_RED, 100, 'key-abc');
        $second = $betting->placeBet($tellerUser, $fight, Fight::SIDE_RED, 100, 'key-abc');

        $this->assertSame($first->id, $second->id);
        $this->assertSame(900, $tellerUser->fresh()->creditBalance());
        $this->assertSame(1, Bet::count());
    }

    public function test_a_teller_without_an_open_shift_cannot_bet(): void
    {
        [$gmUser, $gm, $fight] = $this->makeFight();
        [$tellerUser, $teller] = $this->makeTeller($gm, 1000);

        ShiftSession::where('teller_id', $teller->id)->update([
            'status' => ShiftSession::STATUS_CLOSED,
            'closed_at' => now(),
        ]);

        $betting = app(BettingService::class);
        $betting->openBetting($fight, $gmUser);

        $this->expectException(BettingException::class);

        $betting->placeBet($tellerUser, $fight, Fight::SIDE_RED, 100);
    }
}
