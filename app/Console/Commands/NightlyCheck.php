<?php

namespace App\Console\Commands;

use App\Services\ReconciliationService;
use App\Services\ReportService;
use App\Services\WalletService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * One nightly sweep: ledger integrity, sealed-report hash chain, and the
 * end-of-day reconciliation for a single date. Every problem is written to the
 * application log so an unattended run leaves a trail, and the exit code is
 * non-zero so Task Scheduler can be pointed at it directly.
 */
class NightlyCheck extends Command
{
    protected $signature = 'system:nightly {--date= : Date to reconcile (Y-m-d, default today)}';

    protected $description = 'Verify the ledger and report chain, then reconcile a day and log any problem';

    public function handle(WalletService $wallets, ReportService $reports, ReconciliationService $reconciliation): int
    {
        $date = $this->option('date') ?: now()->toDateString();
        $problems = [];

        $ledger = $wallets->verify();

        $this->info("Wallets checked: {$ledger['checked']}");

        foreach ($ledger['issues'] as $issue) {
            $problems[] = sprintf(
                'Ledger mismatch %s: wallet %s vs history %s (difference %s)',
                $issue['username'] ?? $issue['user_id'],
                number_format($issue['wallet_balance']),
                number_format($issue['ledger_net']),
                number_format($issue['difference'])
            );
        }

        $chain = $reports->verifyChain();

        $this->info("Report snapshots checked: {$chain['checked']}");

        foreach ($chain['issues'] as $issue) {
            $problems[] = 'Report chain: '.$issue;
        }

        $day = $reconciliation->build($date);

        $this->info(sprintf(
            'Reconciliation %s: %d tellers, %d bets, stake %s, issues %d',
            $day['date'],
            $day['totals']['tellers'],
            $day['totals']['bets'],
            number_format($day['totals']['stake']),
            $day['totals']['issues']
        ));

        foreach ($day['rows'] as $row) {
            foreach ($row['issues'] as $issue) {
                $problems[] = "Reconciliation {$row['code']} ({$row['name']}): {$issue}";
            }
        }

        if ($day['open_shifts'] > 0) {
            $problems[] = "Open shifts left at end of day: {$day['open_shifts']}";
        }

        if ($day['unsettled_fights'] > 0) {
            $problems[] = "Fights not settled: {$day['unsettled_fights']}";
        }

        if ($problems === []) {
            $this->info('Nightly check: OK — nothing to report.');

            Log::info('Nightly check passed', [
                'date' => $day['date'],
                'wallets_checked' => $ledger['checked'],
                'snapshots_checked' => $chain['checked'],
                'bets' => $day['totals']['bets'],
                'credits_in_circulation' => $day['credits_in_circulation'],
            ]);

            return self::SUCCESS;
        }

        foreach ($problems as $problem) {
            $this->error($problem);
            Log::error('Nightly check: '.$problem, ['date' => $day['date']]);
        }

        $this->error(count($problems).' problem(s) found. See storage/logs/laravel.log.');

        return self::FAILURE;
    }
}
