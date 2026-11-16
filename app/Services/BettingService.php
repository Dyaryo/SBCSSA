<?php

namespace App\Services;

use App\Exceptions\BettingException;
use App\Models\Bet;
use App\Models\Event;
use App\Models\Fight;
use App\Models\FightEvent;
use App\Models\User;
use App\Models\WalletTransaction;
use Illuminate\Support\Facades\DB;

/**
 * Fight lifecycle + simulated betting. Every state change is a guarded,
 * transactional operation so two terminals can never double-book a fight.
 */
class BettingService
{
    public function __construct(
        private WalletService $wallets,
        private AuditService $audit
    ) {
    }

    public function openBetting(Fight $fight, User $actor): Fight
    {
        return DB::transaction(function () use ($fight, $actor) {
            $updated = DB::table('fights')
                ->where('id', $fight->id)
                ->whereIn('status', [Fight::STATUS_SCHEDULED, Fight::STATUS_BETTING_LOCKED])
                ->update([
                    'status' => Fight::STATUS_BETTING_OPEN,
                    'betting_opened_at' => now(),
                    'betting_locked_at' => null,
                    'updated_at' => now(),
                ]);

            if ($updated === 0) {
                throw new BettingException('Betting cannot be opened from the current fight status.');
            }

            $fight->refresh();
            $this->log($fight, 'betting_open', 'Betting window opened.', $actor);
            $this->audit->log('fight.betting_opened', $fight, $actor, [], ['status' => $fight->status]);

            return $fight;
        });
    }

    public function lockBetting(Fight $fight, User $actor): Fight
    {
        return DB::transaction(function () use ($fight, $actor) {
            $updated = DB::table('fights')
                ->where('id', $fight->id)
                ->where('status', Fight::STATUS_BETTING_OPEN)
                ->update([
                    'status' => Fight::STATUS_BETTING_LOCKED,
                    'betting_locked_at' => now(),
                    'updated_at' => now(),
                ]);

            if ($updated === 0) {
                throw new BettingException('Betting is not open on this fight.');
            }

            $fight->refresh();
            $this->log($fight, 'betting_locked', 'Betting closed. No new bets accepted.', $actor);
            $this->audit->log('fight.betting_locked', $fight, $actor, [], ['status' => $fight->status]);

            return $fight;
        });
    }

    /**
     * Lock or unlock one side of a fight. Used when the windows are all piling
     * onto the same corner: the pit shuts that side instead of closing the
     * whole fight, so the other side stays open for business.
     */
    public function setSideLock(Fight $fight, string $side, bool $locked, User $actor): Fight
    {
        if (! in_array($side, [Fight::SIDE_RED, Fight::SIDE_BLUE], true)) {
            throw new BettingException('Only meron or wala can be locked.');
        }

        return DB::transaction(function () use ($fight, $side, $locked, $actor) {
            $lockedFight = Fight::whereKey($fight->id)->lockForUpdate()->firstOrFail();

            if ($lockedFight->isTerminal()) {
                throw new BettingException('This fight is already finalised.');
            }

            if ($lockedFight->status !== Fight::STATUS_BETTING_OPEN) {
                throw new BettingException('Open the betting window before locking a side.');
            }

            $column = $side === Fight::SIDE_RED ? 'red_locked' : 'blue_locked';
            $lockedFight->update([$column => $locked]);

            $label = Fight::sideTitle($side);

            $this->log(
                $lockedFight,
                $locked ? 'side_locked' : 'side_unlocked',
                $label.($locked ? ' locked — no new bets on this side.' : ' unlocked — bets accepted again.'),
                $actor
            );

            $this->audit->log($locked ? 'fight.side_locked' : 'fight.side_unlocked', $lockedFight, $actor, [], [
                'side' => $side,
                'locked' => $locked,
            ]);

            return $lockedFight->refresh();
        });
    }

    public function startFight(Fight $fight, User $actor): Fight
    {
        return DB::transaction(function () use ($fight, $actor) {
            $updated = DB::table('fights')
                ->where('id', $fight->id)
                ->where('status', Fight::STATUS_BETTING_LOCKED)
                ->update([
                    'status' => Fight::STATUS_LIVE,
                    'started_at' => now(),
                    'updated_at' => now(),
                ]);

            if ($updated === 0) {
                throw new BettingException('Lock betting before starting the fight.');
            }

            $fight->refresh();
            $this->log($fight, 'live', 'Fight marked live.', $actor);
            $this->audit->log('fight.started', $fight, $actor, [], ['status' => $fight->status]);

            return $fight;
        });
    }

    public function placeBet(
        User $tellerUser,
        Fight $fight,
        string $side,
        int $amount,
        ?string $idempotencyKey = null
    ): Bet {
        if (! in_array($side, Fight::sides(), true)) {
            throw new BettingException('Select meron, wala or draw.');
        }

        $teller = $tellerUser->tellerProfile;

        if (! $teller || $teller->status !== 'active') {
            throw new BettingException('This POS account is not active.');
        }

        $min = (int) config('pos.min_bet');
        $max = (int) config('pos.max_bet');

        if ($amount < $min) {
            throw new BettingException('Minimum simulated bet is '.number_format($min).' credits.');
        }

        if ($amount > $max) {
            throw new BettingException('Maximum simulated bet is '.number_format($max).' credits.');
        }

        return DB::transaction(function () use ($tellerUser, $teller, $fight, $side, $amount, $idempotencyKey) {
            if ($idempotencyKey) {
                $existing = Bet::where('idempotency_key', $idempotencyKey)->first();

                if ($existing) {
                    return $existing;
                }
            }

            // Row lock: the betting state is re-read inside the transaction.
            $locked = Fight::whereKey($fight->id)->lockForUpdate()->firstOrFail();

            if (! $locked->isBettingOpen()) {
                throw new BettingException('Betting is closed for this fight.');
            }

            if ($locked->event && $locked->event->status === Event::STATUS_CANCELLED) {
                throw new BettingException('This event has been cancelled.');
            }

            if ($locked->isSideLocked($side)) {
                throw new BettingException(
                    Fight::sideTitle($side).' is locked by the pit on this fight. Take the other side or wait for the Game Master to unlock it.'
                );
            }

            // The teller and the card must belong to the same Game Master.
            // Only a teller explicitly attached to a Game Master is restricted to
            // that pit. Unassigned tills take money on the open card whoever runs
            // it, which is how the desk is staffed now.
            if ($teller->game_master_id
                && $locked->event
                && (int) $locked->event->game_master_id !== (int) $teller->game_master_id) {
                throw new BettingException('This fight is on another Game Master\'s card. This window only takes bets for its own Game Master.');
            }

            $shift = $teller->openShift();

            if (! $shift) {
                throw new BettingException('Open a shift before accepting bets.');
            }

            $tellerBetCount = $teller->bets()->where('fight_id', $locked->id)->count();

            if ($tellerBetCount >= (int) config('pos.max_bets_per_fight')) {
                throw new BettingException('This terminal has reached the per-fight bet limit.');
            }

            // Guard against a double-click on a slow terminal: the same teller,
            // fight, side and amount inside a few seconds is treated as a repeat.
            $duplicate = Bet::where('teller_id', $teller->id)
                ->where('fight_id', $locked->id)
                ->where('side', $side)
                ->where('amount_credits', $amount)
                ->where('placed_at', '>=', now()->subSeconds(5))
                ->exists();

            if ($duplicate) {
                throw new BettingException('This looks like a duplicate submission. Wait a few seconds before repeating the same bet.');
            }

            $odds = $locked->oddsFor($side);
            // The stop ratio can cap the payout below the posted odds.
            $payout = $locked->payoutFor($side, $amount);

            $bet = Bet::create([
                'fight_id' => $locked->id,
                'teller_id' => $teller->id,
                'shift_session_id' => $shift->id,
                'side' => $side,
                'amount_credits' => $amount,
                'odds_at_placement' => $odds,
                'potential_payout' => $payout,
                'status' => Bet::STATUS_ACTIVE,
                'placed_at' => now(),
                'idempotency_key' => $idempotencyKey,
            ]);

            $this->wallets->debit($tellerUser, $amount, WalletTransaction::TYPE_BET_HOLD, [
                'fight_id' => $locked->id,
                'bet_id' => $bet->id,
                'performed_by' => $tellerUser->id,
                'idempotency_key' => $idempotencyKey ? $idempotencyKey.':hold' : null,
                'reason' => 'Bet hold — '.$locked->label(),
            ]);

            $this->log($locked, 'bet_placed', 'Bet #'.$bet->id.' '.$side.' for '.number_format($amount).' credits.', $tellerUser);

            return $bet;
        });
    }

    public function settle(Fight $fight, string $winner, User $actor): Fight
    {
        return $this->declareWinner(
            $fight,
            $winner,
            $actor,
            $winner === Fight::SIDE_RED ? Fight::RESULT_MERON : Fight::RESULT_WALA
        );
    }

    /**
     * Grade a fight the way the pit calls it: meron, wala, draw or cancelled.
     * Meron and wala pay one side, draw pays the draw tickets at the card's
     * ratio (x7 by default), and cancelled refunds every live stake.
     */
    public function declareResult(Fight $fight, string $result, User $actor, ?string $reason = null): Fight
    {
        return match ($result) {
            Fight::RESULT_MERON => $this->declareWinner($fight, Fight::SIDE_RED, $actor, Fight::RESULT_MERON),
            Fight::RESULT_WALA => $this->declareWinner($fight, Fight::SIDE_BLUE, $actor, Fight::RESULT_WALA),
            Fight::RESULT_DRAW => $this->declareDraw($fight, $actor),
            Fight::RESULT_CANCELLED => $this->cancelFight($fight, (string) $reason, $actor),
            default => throw new BettingException('Choose meron, wala, draw or cancelled.'),
        };
    }

    private function declareWinner(Fight $fight, string $winner, User $actor, string $result): Fight
    {
        if (! in_array($winner, [Fight::SIDE_RED, Fight::SIDE_BLUE], true)) {
            throw new BettingException('Declare either the red or blue side as winner. Use void for refunds.');
        }

        return DB::transaction(function () use ($fight, $winner, $actor, $result) {
            $locked = Fight::whereKey($fight->id)->lockForUpdate()->firstOrFail();

            if ($locked->isTerminal()) {
                throw new BettingException('This fight is already finalised.');
            }

            if (! in_array($locked->status, [Fight::STATUS_BETTING_LOCKED, Fight::STATUS_LIVE], true)) {
                throw new BettingException('Lock betting before declaring a result.');
            }

            $bets = $locked->bets()->where('status', Bet::STATUS_ACTIVE)->with('teller.user')->get();
            $payoutTotal = 0;

            foreach ($bets as $bet) {
                $tellerUser = $bet->teller?->user;

                if (! $tellerUser) {
                    continue;
                }

                if ($bet->side === $winner) {
                    $this->wallets->credit($tellerUser, (int) $bet->potential_payout, WalletTransaction::TYPE_PAYOUT, [
                        'fight_id' => $locked->id,
                        'bet_id' => $bet->id,
                        'performed_by' => $actor->id,
                        'reason' => 'Payout — '.$locked->label(),
                    ]);

                    $bet->status = Bet::STATUS_WON;
                    $payoutTotal += (int) $bet->potential_payout;
                } else {
                    $bet->status = Bet::STATUS_LOST;
                }

                $bet->settled_at = now();
                $bet->save();
            }

            $locked->update([
                'status' => Fight::STATUS_SETTLED,
                'winner' => $winner,
                'result' => $result,
                'declared_by' => $actor->id,
                'declared_at' => now(),
                'ended_at' => now(),
            ]);

            $this->log($locked, 'result', 'Result: '.strtoupper($result).'. Payouts: '.number_format($payoutTotal).' credits.', $actor);
            $this->audit->log('fight.result_declared', $locked, $actor, [], [
                'result' => $result,
                'winner' => $winner,
                'payout_total' => $payoutTotal,
                'settled_bets' => $bets->count(),
            ]);

            return $locked->refresh();
        });
    }

    private function declareDraw(Fight $fight, User $actor): Fight
    {
        return DB::transaction(function () use ($fight, $actor) {
            $locked = Fight::whereKey($fight->id)->lockForUpdate()->firstOrFail();

            if ($locked->isTerminal()) {
                throw new BettingException('This fight is already finalised.');
            }

            if (! in_array($locked->status, [Fight::STATUS_BETTING_LOCKED, Fight::STATUS_LIVE], true)) {
                throw new BettingException('Lock betting before declaring a result.');
            }

            $bets = $locked->bets()->where('status', Bet::STATUS_ACTIVE)->with('teller.user')->get();
            $payoutTotal = 0;

            foreach ($bets as $bet) {
                $tellerUser = $bet->teller?->user;

                if (! $tellerUser) {
                    continue;
                }

                if ($bet->side === Fight::SIDE_DRAW) {
                    $this->wallets->credit($tellerUser, (int) $bet->potential_payout, WalletTransaction::TYPE_PAYOUT, [
                        'fight_id' => $locked->id,
                        'bet_id' => $bet->id,
                        'performed_by' => $actor->id,
                        'reason' => 'Draw payout — '.$locked->label(),
                    ]);

                    $bet->status = Bet::STATUS_WON;
                    $payoutTotal += (int) $bet->potential_payout;
                } else {
                    $bet->status = Bet::STATUS_LOST;
                }

                $bet->settled_at = now();
                $bet->save();
            }

            $ratio = number_format($locked->drawRatio(), 3);

            $locked->update([
                'status' => Fight::STATUS_SETTLED,
                'winner' => Fight::SIDE_DRAW,
                'result' => Fight::RESULT_DRAW,
                'declared_by' => $actor->id,
                'declared_at' => now(),
                'ended_at' => now(),
                'void_reason' => 'Draw — draw tickets paid '.$ratio.'×; meron and wala lost.',
            ]);

            $this->log($locked, 'result', 'Result: DRAW. Paid '.number_format($payoutTotal).' credits at '.$ratio.'×.', $actor);
            $this->audit->log('fight.result_declared', $locked, $actor, [], [
                'result' => Fight::RESULT_DRAW,
                'draw_ratio' => (float) $locked->drawRatio(),
                'payout_total' => $payoutTotal,
                'settled_bets' => $bets->count(),
            ]);

            return $locked->refresh();
        });
    }

    /**
     * Correct a declared result. Money that went to the wrong winner is taken
     * back through the ledger, then the fight is graded again with the right
     * result. Refused once any winning slip has actually been paid over the
     * counter — that money is out of the system and the pit cannot re-open it.
     */
    public function regrade(Fight $fight, string $newResult, User $actor, ?string $reason = null): Fight
    {
        if (! in_array($newResult, [Fight::RESULT_MERON, Fight::RESULT_WALA, Fight::RESULT_DRAW, Fight::RESULT_CANCELLED], true)) {
            throw new BettingException('Choose meron, wala, draw or cancelled as the corrected result.');
        }

        if ($newResult === Fight::RESULT_CANCELLED && trim((string) $reason) === '') {
            throw new BettingException('A reason is required when the corrected result is cancelled.');
        }

        return DB::transaction(function () use ($fight, $newResult, $actor, $reason) {
            $locked = Fight::whereKey($fight->id)->lockForUpdate()->firstOrFail();

            if ($locked->status !== Fight::STATUS_SETTLED) {
                throw new BettingException('Only a fight with a declared result can be regraded.');
            }

            if ($locked->result === $newResult) {
                throw new BettingException(strtoupper($newResult).' is already the declared result.');
            }

            $oldResult = (string) $locked->result;
            $bets = $locked->bets()->with('teller.user')->get();

            if ($bets->whereNotNull('paid_out_at')->isNotEmpty()) {
                throw new BettingException('A winning slip on this fight has already been paid out. Regrading is closed once winnings have left the counter.');
            }

            // Take back what the wrong winner was credited. The ledger keeps
            // both movements, so the correction is auditable.
            $clawback = 0;

            foreach ($bets->where('status', Bet::STATUS_WON) as $bet) {
                $tellerUser = $bet->teller?->user;

                if (! $tellerUser) {
                    continue;
                }

                $amount = (int) $bet->potential_payout;

                $this->wallets->adjust($tellerUser, -$amount, WalletTransaction::TYPE_ADJUSTMENT, [
                    'fight_id' => $locked->id,
                    'bet_id' => $bet->id,
                    'performed_by' => $actor->id,
                    'reason' => 'Result regraded from '.strtoupper($oldResult).' — payout reversed on '.$locked->label(),
                ]);

                $clawback += $amount;
            }

            $locked->bets()->update([
                'status' => Bet::STATUS_ACTIVE,
                'settled_at' => null,
                'paid_out_at' => null,
                'paid_out_by' => null,
            ]);

            // Reopen grading on the same fight. The bets go through the normal
            // winner/draw/cancel path so the wallets end in the right place.
            $locked->update([
                'status' => Fight::STATUS_BETTING_LOCKED,
                'winner' => null,
                'result' => null,
                'declared_by' => null,
                'declared_at' => null,
                'ended_at' => null,
                'void_reason' => null,
            ]);

            $this->log($locked, 'regrade', 'Result regraded from '.strtoupper($oldResult).' to '.strtoupper($newResult).'.', $actor);

            $updated = match ($newResult) {
                Fight::RESULT_MERON => $this->declareWinner($locked, Fight::SIDE_RED, $actor, Fight::RESULT_MERON),
                Fight::RESULT_WALA => $this->declareWinner($locked, Fight::SIDE_BLUE, $actor, Fight::RESULT_WALA),
                Fight::RESULT_DRAW => $this->declareDraw($locked, $actor),
                Fight::RESULT_CANCELLED => $this->cancelFight($locked, (string) $reason, $actor),
            };

            $this->audit->log('fight.regraded', $updated, $actor, ['result' => $oldResult], [
                'result' => $newResult,
                'reason' => $reason,
                'reversed_payouts' => $clawback,
            ]);

            return $updated->refresh();
        });
    }

    public function cancelFight(Fight $fight, string $reason, User $actor): Fight
    {
        $reason = trim($reason);

        if ($reason === '') {
            throw new BettingException('A reason is required when cancelling a fight.');
        }

        return DB::transaction(function () use ($fight, $reason, $actor) {
            $locked = Fight::whereKey($fight->id)->lockForUpdate()->firstOrFail();

            if ($locked->isTerminal()) {
                throw new BettingException('This fight is already finalised.');
            }

            $refunded = $this->refundActiveBets($locked, $actor, 'Fight cancelled — '.$reason);

            $locked->update([
                'status' => Fight::STATUS_CANCELLED,
                'result' => Fight::RESULT_CANCELLED,
                'void_reason' => $reason,
                'declared_by' => $actor->id,
                'declared_at' => now(),
                'ended_at' => now(),
            ]);

            $this->log($locked, 'result', 'Result: CANCELLED ('.$reason.'). Refunded '.number_format($refunded).' credits.', $actor);
            $this->audit->log('fight.cancelled', $locked, $actor, [], ['reason' => $reason, 'refunded' => $refunded]);

            return $locked->refresh();
        });
    }

    public function voidFight(Fight $fight, string $reason, User $actor): Fight
    {
        $reason = trim($reason);

        if ($reason === '') {
            throw new BettingException('A reason is required when voiding a fight.');
        }

        return DB::transaction(function () use ($fight, $reason, $actor) {
            $locked = Fight::whereKey($fight->id)->lockForUpdate()->firstOrFail();

            if ($locked->isTerminal()) {
                throw new BettingException('This fight is already finalised.');
            }

            $refunded = $this->refundActiveBets($locked, $actor, 'Fight voided — '.$reason);

            $locked->update([
                'status' => Fight::STATUS_VOID,
                'void_reason' => $reason,
                'declared_by' => $actor->id,
                'declared_at' => now(),
                'ended_at' => now(),
            ]);

            $this->log($locked, 'void', 'Fight voided. Refunded '.number_format($refunded).' credits.', $actor);
            $this->audit->log('fight.voided', $locked, $actor, [], ['reason' => $reason, 'refunded' => $refunded]);

            return $locked->refresh();
        });
    }

    public function voidBet(Bet $bet, string $reason, User $actor, bool $onlyWhileBettingOpen = false): Bet
    {
        $reason = trim($reason);

        if ($reason === '') {
            throw new BettingException('A reason is required when voiding a bet.');
        }

        return DB::transaction(function () use ($bet, $reason, $actor, $onlyWhileBettingOpen) {
            $locked = Bet::whereKey($bet->id)->lockForUpdate()->firstOrFail();

            if ($locked->status !== Bet::STATUS_ACTIVE) {
                throw new BettingException('Only an active bet can be voided.');
            }

            if ($onlyWhileBettingOpen) {
                // A teller may only pull a bet back while the window is still
                // open. Lock the fight row so a concurrent pit lock cannot be
                // overtaken by a cancellation.
                $fight = Fight::whereKey($locked->fight_id)->lockForUpdate()->first();

                if (! $fight || $fight->status !== Fight::STATUS_BETTING_OPEN) {
                    throw new BettingException('Betting is closed on this fight. Ask the Game Master to void the bet.');
                }
            }

            if ($locked->fight && $locked->fight->isTerminal()) {
                throw new BettingException('The fight is already finalised.');
            }

            $tellerUser = $locked->teller?->user;

            if (! $tellerUser) {
                throw new BettingException('The teller account for this bet could not be found.');
            }

            $this->wallets->credit($tellerUser, (int) $locked->amount_credits, WalletTransaction::TYPE_VOID_REFUND, [
                'fight_id' => $locked->fight_id,
                'bet_id' => $locked->id,
                'performed_by' => $actor->id,
                'reason' => 'Bet voided — '.$reason,
            ]);

            $locked->update([
                'status' => Bet::STATUS_VOID,
                'voided_by' => $actor->id,
                'void_reason' => $reason,
                'settled_at' => now(),
            ]);

            $this->audit->log('bet.voided', $locked, $actor, [], ['reason' => $reason, 'amount' => (int) $locked->amount_credits]);

            return $locked->refresh();
        });
    }

    private function refundActiveBets(Fight $fight, User $actor, string $reason): int
    {
        $refunded = 0;

        $bets = $fight->bets()->where('status', Bet::STATUS_ACTIVE)->with('teller.user')->get();

        foreach ($bets as $bet) {
            $tellerUser = $bet->teller?->user;

            if (! $tellerUser) {
                continue;
            }

            $this->wallets->credit($tellerUser, (int) $bet->amount_credits, WalletTransaction::TYPE_VOID_REFUND, [
                'fight_id' => $fight->id,
                'bet_id' => $bet->id,
                'performed_by' => $actor->id,
                'reason' => $reason,
            ]);

            $bet->update([
                'status' => Bet::STATUS_REFUNDED,
                'voided_by' => $actor->id,
                'void_reason' => $reason,
                'settled_at' => now(),
            ]);

            $refunded += (int) $bet->amount_credits;
        }

        return $refunded;
    }

    private function log(Fight $fight, string $kind, string $note, ?User $actor): void
    {
        FightEvent::create([
            'fight_id' => $fight->id,
            'kind' => $kind,
            'note' => $note,
            'created_by' => $actor?->id,
            'created_at' => now(),
        ]);
    }
}
