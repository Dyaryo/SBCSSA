<?php

namespace App\Services;

use App\Models\Bet;
use App\Models\Fight;
use App\Models\ShiftSession;
use App\Models\TellerProfile;
use App\Models\VirtualWallet;
use App\Models\WalletTransaction;
use Illuminate\Support\Carbon;

/**
 * End-of-day reconciliation: rebuilds each teller's day from the bet table and
 * cross-checks it against the immutable wallet ledger. A row that disagrees is
 * flagged, which is how a missing, duplicated or edited ledger entry surfaces
 * without anyone reading SQL by hand.
 */
class ReconciliationService
{
    public function build(string $date): array
    {
        $day = Carbon::parse($date);
        $start = $day->copy()->startOfDay();
        $end = $day->copy()->endOfDay();

        $tellers = TellerProfile::with(['user', 'gameMaster.user'])
            ->orderBy('teller_code')
            ->get();

        $bets = Bet::whereBetween('placed_at', [$start, $end])
            ->selectRaw("teller_id,
                COUNT(*) AS bet_count,
                COALESCE(SUM(amount_credits), 0) AS stake,
                COALESCE(SUM(CASE WHEN status = 'won' THEN potential_payout ELSE 0 END), 0) AS payout,
                COALESCE(SUM(CASE WHEN status IN ('void','refunded') THEN amount_credits ELSE 0 END), 0) AS refunded,
                COALESCE(SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END), 0) AS active_count")
            ->groupBy('teller_id')
            ->get()
            ->keyBy('teller_id');

        $ledger = WalletTransaction::whereBetween('created_at', [$start, $end])
            ->selectRaw("user_id,
                COALESCE(SUM(CASE WHEN type = 'bet_hold' THEN amount_credits ELSE 0 END), 0) AS holds,
                COALESCE(SUM(CASE WHEN type = 'payout' THEN amount_credits ELSE 0 END), 0) AS payouts,
                COALESCE(SUM(CASE WHEN type = 'void_refund' THEN amount_credits ELSE 0 END), 0) AS refunds,
                COALESCE(SUM(CASE WHEN type = 'seed' THEN amount_credits ELSE 0 END), 0) AS seeds,
                COALESCE(SUM(CASE WHEN type IN ('adjustment','shift_correction') THEN amount_credits ELSE 0 END), 0) AS adjustments")
            ->groupBy('user_id')
            ->get()
            ->keyBy('user_id');

        $shifts = ShiftSession::whereBetween('opened_at', [$start, $end])
            ->selectRaw("teller_id,
                COUNT(*) AS shift_count,
                COALESCE(SUM(CASE WHEN status = 'open' THEN 1 ELSE 0 END), 0) AS open_count,
                COALESCE(SUM(CASE WHEN status = 'force_closed' THEN 1 ELSE 0 END), 0) AS forced_count")
            ->groupBy('teller_id')
            ->get()
            ->keyBy('teller_id');

        $rows = [];

        foreach ($tellers as $teller) {
            $b = $bets->get($teller->id);
            $l = $ledger->get($teller->user_id);
            $s = $shifts->get($teller->id);

            $stake = (int) ($b->stake ?? 0);
            $payout = (int) ($b->payout ?? 0);
            $refunded = (int) ($b->refunded ?? 0);
            $holds = (int) ($l->holds ?? 0);
            $ledgerPayout = (int) ($l->payouts ?? 0);
            $ledgerRefund = (int) ($l->refunds ?? 0);

            $issues = [];

            if ($stake !== $holds) {
                $issues[] = 'Stake '.number_format($stake).' ≠ ledger holds '.number_format($holds);
            }

            if ($payout !== $ledgerPayout) {
                $issues[] = 'Payout '.number_format($payout).' ≠ ledger payouts '.number_format($ledgerPayout);
            }

            if ($refunded !== $ledgerRefund) {
                $issues[] = 'Refunds '.number_format($refunded).' ≠ ledger refunds '.number_format($ledgerRefund);
            }

            $rows[] = [
                'teller_id' => $teller->id,
                'code' => $teller->teller_code,
                'name' => $teller->displayName(),
                'game_master' => $teller->gameMaster?->displayName() ?? '—',
                'status' => $teller->status,
                'shifts' => (int) ($s->shift_count ?? 0),
                'open_shifts' => (int) ($s->open_count ?? 0),
                'forced_shifts' => (int) ($s->forced_count ?? 0),
                'bets' => (int) ($b->bet_count ?? 0),
                'active_bets' => (int) ($b->active_count ?? 0),
                'stake' => $stake,
                'payout' => $payout,
                'refunded' => $refunded,
                'seeded' => (int) ($l->seeds ?? 0),
                'adjustments' => (int) ($l->adjustments ?? 0),
                'net' => $payout - $stake + $refunded,
                'balance' => (int) (VirtualWallet::where('user_id', $teller->user_id)->value('balance_credits') ?? 0),
                'ledger_holds' => $holds,
                'ledger_payout' => $ledgerPayout,
                'ledger_refund' => $ledgerRefund,
                'issues' => $issues,
            ];
        }

        return [
            'date' => $day->toDateString(),
            'rows' => $rows,
            'totals' => [
                'tellers' => count($rows),
                'shifts' => (int) array_sum(array_column($rows, 'shifts')),
                'bets' => (int) array_sum(array_column($rows, 'bets')),
                'stake' => (int) array_sum(array_column($rows, 'stake')),
                'payout' => (int) array_sum(array_column($rows, 'payout')),
                'refunded' => (int) array_sum(array_column($rows, 'refunded')),
                'net' => (int) array_sum(array_column($rows, 'net')),
                'issues' => (int) array_sum(array_map(fn (array $row) => count($row['issues']), $rows)),
            ],
            'credits_in_circulation' => (int) VirtualWallet::sum('balance_credits'),
            'open_shifts' => (int) ShiftSession::where('status', ShiftSession::STATUS_OPEN)->count(),
            'unsettled_fights' => (int) Fight::whereIn('status', [
                Fight::STATUS_BETTING_OPEN,
                Fight::STATUS_BETTING_LOCKED,
                Fight::STATUS_LIVE,
            ])->count(),
        ];
    }

    public function toCsv(array $data): string
    {
        $handle = fopen('php://temp', 'r+');

        fputcsv($handle, [
            'Teller', 'Name', 'Game Master', 'Status', 'Shifts', 'Open shifts', 'Forced closes',
            'Bets', 'Pending', 'Stake', 'Payout', 'Refunded', 'Net', 'Balance',
            'Ledger holds', 'Ledger payouts', 'Ledger refunds', 'Issues',
        ]);

        foreach ($data['rows'] as $row) {
            fputcsv($handle, [
                $row['code'], $row['name'], $row['game_master'], $row['status'], $row['shifts'],
                $row['open_shifts'], $row['forced_shifts'], $row['bets'], $row['active_bets'],
                $row['stake'], $row['payout'], $row['refunded'], $row['net'], $row['balance'],
                $row['ledger_holds'], $row['ledger_payout'], $row['ledger_refund'],
                implode(' / ', $row['issues']),
            ]);
        }

        fputcsv($handle, []);
        fputcsv($handle, ['TOTAL', '', '', '', $data['totals']['shifts'], '', '', $data['totals']['bets'], '', $data['totals']['stake'], $data['totals']['payout'], $data['totals']['refunded'], $data['totals']['net'], '', '', '', '', 'Issues: '.$data['totals']['issues']]);

        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        return $csv ?: '';
    }
}
