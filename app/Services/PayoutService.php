<?php

namespace App\Services;

use App\Exceptions\BettingException;
use App\Models\Bet;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Window-side payout bookkeeping.
 *
 * The credits themselves move inside the ledger when the Game Master grades a
 * fight. This service records that the teller actually handed the money over,
 * so the same winning slip cannot be paid out twice.
 */
class PayoutService
{
    public function __construct(private AuditService $audit)
    {
    }

    public function record(Bet $bet, User $tellerUser, User $actor): Bet
    {
        return DB::transaction(function () use ($bet, $tellerUser, $actor) {
            $locked = Bet::whereKey($bet->id)->lockForUpdate()->firstOrFail();

            if ($locked->status !== Bet::STATUS_WON) {
                throw new BettingException('Only a winning slip can be paid out.');
            }

            if ($locked->paid_out_at) {
                throw new BettingException(
                    'This slip was already paid out on '.$locked->paid_out_at->format('d M Y H:i').'. Do not pay it again.'
                );
            }

            $locked->update([
                'paid_out_at' => now(),
                'paid_out_by' => $actor->id,
            ]);

            $this->audit->log('bet.paid_out', $locked, $actor, [], [
                'bet_id' => $locked->id,
                'payout' => (int) $locked->potential_payout,
                'teller_id' => $tellerUser->id,
            ]);

            return $locked->refresh();
        });
    }
}
