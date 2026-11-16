<?php

namespace App\Services;

use App\Exceptions\InsufficientCreditsException;
use App\Models\User;
use App\Models\VirtualWallet;
use App\Models\WalletTransaction;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Every credit movement in the system goes through this service. Balances are
 * never written directly by controllers.
 */
class WalletService
{
    public function walletFor(User $user, bool $lock = false): VirtualWallet
    {
        $user->wallet()->firstOrCreate([], [
            'balance_credits' => 0,
            'version' => 0,
        ]);

        $query = VirtualWallet::where('user_id', $user->id);

        if ($lock) {
            $query->lockForUpdate();
        }

        return $query->firstOrFail();
    }

    /**
     * Apply a credit or debit movement and append the matching ledger row.
     * Callers should wrap multi-step work in DB::transaction().
     *
     * @param  array{fight_id?:int|null,bet_id?:int|null,reference_no?:string|null,idempotency_key?:string|null,performed_by?:int|null,reason?:string|null}  $options
     */
    public function apply(
        User $user,
        string $direction,
        int $amount,
        string $type,
        array $options = []
    ): WalletTransaction {
        if ($amount <= 0) {
            throw new \InvalidArgumentException('Credit movement amount must be positive.');
        }

        $wallet = $this->walletFor($user, true);
        $current = (int) $wallet->balance_credits;
        $delta = $direction === WalletTransaction::DIRECTION_CREDIT ? $amount : -$amount;

        if ($current + $delta < 0) {
            throw new InsufficientCreditsException(
                'Insufficient virtual credits. Current balance: '.number_format($current).'.'
            );
        }

        $wallet->balance_credits = $current + $delta;
        $wallet->version = (int) $wallet->version + 1;
        $wallet->save();

        return WalletTransaction::create([
            'wallet_id' => $wallet->id,
            'user_id' => $user->id,
            'fight_id' => $options['fight_id'] ?? null,
            'bet_id' => $options['bet_id'] ?? null,
            'type' => $type,
            'direction' => $direction,
            'amount_credits' => $amount,
            'balance_after' => (int) $wallet->balance_credits,
            'reference_no' => $options['reference_no'] ?? $this->reference(),
            'idempotency_key' => $options['idempotency_key'] ?? null,
            'performed_by' => $options['performed_by'] ?? null,
            'reason' => $options['reason'] ?? null,
            'created_at' => now(),
        ]);
    }

    /**
     * Apply a signed correction without the "cannot go below zero" guard.
     * Used when a result is regraded: the wrong winner's payout has to come
     * back even if that terminal has since played the credits away.
     */
    public function adjust(User $user, int $delta, string $type, array $options = []): WalletTransaction
    {
        if ($delta === 0) {
            throw new \InvalidArgumentException('Adjustment amount must not be zero.');
        }

        return DB::transaction(function () use ($user, $delta, $type, $options) {
            $wallet = $this->walletFor($user, true);
            $wallet->balance_credits = (int) $wallet->balance_credits + $delta;
            $wallet->version = (int) $wallet->version + 1;
            $wallet->save();

            return WalletTransaction::create([
                'wallet_id' => $wallet->id,
                'user_id' => $user->id,
                'fight_id' => $options['fight_id'] ?? null,
                'bet_id' => $options['bet_id'] ?? null,
                'type' => $type,
                'direction' => $delta > 0 ? WalletTransaction::DIRECTION_CREDIT : WalletTransaction::DIRECTION_DEBIT,
                'amount_credits' => abs($delta),
                'balance_after' => (int) $wallet->balance_credits,
                'reference_no' => $options['reference_no'] ?? $this->reference(),
                'idempotency_key' => $options['idempotency_key'] ?? null,
                'performed_by' => $options['performed_by'] ?? null,
                'reason' => $options['reason'] ?? null,
                'created_at' => now(),
            ]);
        });
    }

    public function credit(User $user, int $amount, string $type, array $options = []): WalletTransaction
    {
        return $this->apply($user, WalletTransaction::DIRECTION_CREDIT, $amount, $type, $options);
    }

    public function debit(User $user, int $amount, string $type, array $options = []): WalletTransaction
    {
        return $this->apply($user, WalletTransaction::DIRECTION_DEBIT, $amount, $type, $options);
    }

    public function seedCredits(User $user, int $amount, ?User $actor, ?string $reason = null): WalletTransaction
    {
        return DB::transaction(fn () => $this->credit($user, $amount, WalletTransaction::TYPE_SEED, [
            'performed_by' => $actor?->id,
            'reason' => $reason ?? 'Virtual credit allocation',
            'reference_no' => $this->reference(),
        ]));
    }

    /**
     * Compare every wallet balance against the sum of its ledger rows.
     *
     * @return array{checked:int,issues:array<int,array<string,mixed>>}
     */
    public function verify(): array
    {
        $net = DB::table('wallet_transactions')
            ->selectRaw("user_id, SUM(CASE WHEN direction = 'credit' THEN amount_credits ELSE -amount_credits END) AS net")
            ->groupBy('user_id')
            ->pluck('net', 'user_id');

        $issues = [];
        $checked = 0;

        foreach (VirtualWallet::with('user')->get() as $wallet) {
            $checked++;
            $expected = (int) ($net[$wallet->user_id] ?? 0);

            if ($expected !== (int) $wallet->balance_credits) {
                $issues[] = [
                    'user_id' => $wallet->user_id,
                    'username' => $wallet->user?->username,
                    'wallet_balance' => (int) $wallet->balance_credits,
                    'ledger_net' => $expected,
                    'difference' => (int) $wallet->balance_credits - $expected,
                ];
            }
        }

        return ['checked' => $checked, 'issues' => $issues];
    }

    public function reference(): string
    {
        return 'TXN-'.now()->format('ymd').'-'.Str::upper(Str::random(8));
    }
}
