<?php

namespace App\Services;

use App\Models\Bet;
use App\Models\ShiftSession;

/**
 * Builds the data printed on a bet receipt and on a shift closeout slip.
 * The short slip code is derived from the record itself so a paper slip can be
 * matched back to the exact row in the database.
 */
class ShiftSlipService
{
    public function __construct(private QrService $qr)
    {
    }

    public function forBet(Bet $bet): array
    {
        $bet->loadMissing(['fight.event', 'fight.redCock', 'fight.blueCock', 'teller.user', 'shift.workstation']);

        $code = $this->code('BET', $bet->id, (int) $bet->amount_credits, (string) $bet->placed_at);

        return [
            'code' => $code,
            // Scanned by the teller's lookup box (or a phone camera) to show
            // the exact bet and what it is worth.
            'qr_svg' => $this->qr->svg($code, 150, 1),
            'bet' => $bet,
            'printed_at' => now(),
        ];
    }

    public function forShift(ShiftSession $shift): array
    {
        $shift->loadMissing(['teller.user', 'workstation', 'bets.fight.event']);

        $bets = $shift->bets->sortBy('id');
        $won = $bets->where('status', Bet::STATUS_WON);
        $voided = $bets->whereIn('status', [Bet::STATUS_VOID, Bet::STATUS_REFUNDED]);

        $stake = (int) $bets->sum('amount_credits');
        $payout = (int) $won->sum('potential_payout');
        $refunded = (int) $voided->sum('amount_credits');

        return [
            'code' => $this->code('SHIFT', $shift->id, $stake, (string) $shift->opened_at),
            'shift' => $shift,
            'bets' => $bets,
            'totals' => [
                'bets' => $bets->count(),
                'red_stake' => (int) $bets->where('side', 'red')->sum('amount_credits'),
                'blue_stake' => (int) $bets->where('side', 'blue')->sum('amount_credits'),
                'draw_stake' => (int) $bets->where('side', 'draw')->sum('amount_credits'),
                'stake' => $stake,
                'payout' => $payout,
                'refunded' => $refunded,
                'won' => $won->count(),
                'lost' => $bets->where('status', Bet::STATUS_LOST)->count(),
                'pending' => $bets->where('status', Bet::STATUS_ACTIVE)->count(),
                'voided' => $voided->count(),
                'net' => $payout - $stake + $refunded,
            ],
            'printed_at' => now(),
        ];
    }

    public function code(string $prefix, int $id, int $amount, string $stamp): string
    {
        $hash = strtoupper(substr(hash('sha256', $prefix.'|'.$id.'|'.$amount.'|'.$stamp), 0, 8));

        return $prefix.'-'.str_pad((string) $id, 6, '0', STR_PAD_LEFT).'-'.$hash;
    }
}
