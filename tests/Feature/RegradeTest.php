<?php

namespace Tests\Feature;

use App\Exceptions\BettingException;
use App\Models\Bet;
use App\Models\Event;
use App\Models\Fight;
use App\Models\User;
use App\Models\WalletTransaction;
use App\Services\BettingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * A Game Master can call the wrong side. Regrading takes back the payout that
 * was credited and settles the fight again with the right result.
 */
class RegradeTest extends TestCase
{
    use RefreshDatabase;

    private function fight(): Fight
    {
        $this->seed();

        $gm = User::where('username', 'gm.north')->firstOrFail();

        $event = Event::create([
            'code' => 'REG-REGRADE',
            'name' => 'Regrade Card',
            'type' => Event::TYPE_REGULAR,
            'game_master_id' => $gm->gameMasterProfile->id,
            'starts_at' => now(),
            'status' => Event::STATUS_OPEN,
        ]);

        $fight = Fight::create([
            'event_id' => $event->id,
            'fight_no' => 1,
            'odds_red' => 1.850,
            'odds_blue' => 2.050,
            'status' => Fight::STATUS_SCHEDULED,
        ]);

        app(BettingService::class)->openBetting($fight, $gm);

        return $fight->refresh();
    }

    private function shiftFor(User $teller): void
    {
        if (! $teller->tellerProfile->openShift()) {
            $this->actingAs($teller)->post('/pos/shift/open')->assertRedirect();
        }
    }

    private function bet(User $teller, Fight $fight, string $side, int $amount): Bet
    {
        $this->shiftFor($teller);

        return app(BettingService::class)->placeBet($teller, $fight, $side, $amount);
    }

    public function test_regrading_pays_the_right_side_and_takes_back_the_wrong_payout(): void
    {
        $fight = $this->fight();
        $gm = User::where('username', 'gm.north')->firstOrFail();
        $redTeller = User::where('username', 'pos01')->firstOrFail();
        $blueTeller = User::where('username', 'pos02')->firstOrFail();

        $redBet = $this->bet($redTeller, $fight, 'red', 1000);
        $blueBet = $this->bet($blueTeller, $fight, 'blue', 1000);

        $redAfterStake = $redTeller->fresh()->creditBalance();
        $blueAfterStake = $blueTeller->fresh()->creditBalance();

        app(BettingService::class)->lockBetting($fight, $gm);
        app(BettingService::class)->declareResult($fight, 'meron', $gm);

        $this->assertSame(Bet::STATUS_WON, $redBet->fresh()->status);
        $this->assertSame(Bet::STATUS_LOST, $blueBet->fresh()->status);
        $this->assertSame($redAfterStake + 1850, $redTeller->fresh()->creditBalance());

        app(BettingService::class)->regrade($fight->fresh(), 'wala', $gm, 'Wrong side announced at the pit');

        $this->assertSame(Fight::RESULT_WALA, $fight->fresh()->result);
        $this->assertSame(Bet::STATUS_LOST, $redBet->fresh()->status);
        $this->assertSame(Bet::STATUS_WON, $blueBet->fresh()->status);

        // The wrong payout is gone and the right one is paid.
        $this->assertSame($redAfterStake, $redTeller->fresh()->creditBalance());
        $this->assertSame($blueAfterStake + 2050, $blueTeller->fresh()->creditBalance());

        $this->assertDatabaseHas('wallet_transactions', [
            'bet_id' => $redBet->id,
            'direction' => WalletTransaction::DIRECTION_DEBIT,
            'amount_credits' => 1850,
        ]);
        $this->assertDatabaseHas('wallet_transactions', [
            'bet_id' => $blueBet->id,
            'direction' => WalletTransaction::DIRECTION_CREDIT,
            'amount_credits' => 2050,
        ]);
        $this->assertDatabaseHas('audit_logs', ['action' => 'fight.regraded']);
    }

    public function test_regrading_to_cancelled_refunds_every_stake(): void
    {
        $fight = $this->fight();
        $gm = User::where('username', 'gm.north')->firstOrFail();
        $teller = User::where('username', 'pos01')->firstOrFail();

        $bet = $this->bet($teller, $fight, 'red', 500);
        $afterStake = $teller->fresh()->creditBalance();

        app(BettingService::class)->lockBetting($fight, $gm);
        app(BettingService::class)->declareResult($fight, 'meron', $gm);

        app(BettingService::class)->regrade($fight->fresh(), 'cancelled', $gm, 'Fight called off after review');

        $this->assertSame(Fight::STATUS_CANCELLED, $fight->fresh()->status);
        $this->assertSame(Bet::STATUS_REFUNDED, $bet->fresh()->status);

        // Cancelling returns the stake that was held when the bet was placed.
        $this->assertSame($afterStake + 500, $teller->fresh()->creditBalance());
    }

    public function test_a_paid_out_slip_blocks_regrading(): void
    {
        $fight = $this->fight();
        $gm = User::where('username', 'gm.north')->firstOrFail();
        $teller = User::where('username', 'pos01')->firstOrFail();

        $bet = $this->bet($teller, $fight, 'red', 1000);

        app(BettingService::class)->lockBetting($fight, $gm);
        app(BettingService::class)->declareResult($fight, 'meron', $gm);

        $bet->fresh()->update(['paid_out_at' => now(), 'paid_out_by' => $teller->id]);

        $this->actingAs($gm)
            ->from('/gm/fights/'.$fight->id)
            ->post('/gm/fights/'.$fight->id.'/regrade', ['result' => 'wala', 'reason' => 'wrong call'])
            ->assertRedirect('/gm/fights/'.$fight->id)
            ->assertSessionHas('error');

        $this->assertSame(Fight::RESULT_MERON, $fight->fresh()->result);
        $this->assertSame(Bet::STATUS_WON, $bet->fresh()->status);
    }

    public function test_the_same_result_and_unsettled_fights_are_refused(): void
    {
        $fight = $this->fight();
        $gm = User::where('username', 'gm.north')->firstOrFail();

        try {
            app(BettingService::class)->regrade($fight, 'meron', $gm, 'nothing to correct');
            $this->fail('An unsettled fight should not be regraded.');
        } catch (BettingException $e) {
            $this->assertStringContainsString('declared result', $e->getMessage());
        }

        app(BettingService::class)->lockBetting($fight, $gm);
        app(BettingService::class)->declareResult($fight, 'meron', $gm);

        try {
            app(BettingService::class)->regrade($fight->fresh(), 'meron', $gm, 'same side');
            $this->fail('Regrading to the same result should be refused.');
        } catch (BettingException $e) {
            $this->assertStringContainsString('already the declared result', $e->getMessage());
        }
    }

    public function test_the_console_offers_regrade_on_a_settled_fight(): void
    {
        $fight = $this->fight();
        $gm = User::where('username', 'gm.north')->firstOrFail();

        app(BettingService::class)->lockBetting($fight, $gm);
        app(BettingService::class)->declareResult($fight, 'meron', $gm);

        $this->actingAs($gm)
            ->get('/gm/fights/'.$fight->id)
            ->assertOk()
            ->assertSee('Regrade result')
            ->assertSee('correct to wala')
            ->assertSee('Why is it being corrected?');
    }
}
