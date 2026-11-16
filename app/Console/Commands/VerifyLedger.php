<?php

namespace App\Console\Commands;

use App\Services\ReportService;
use App\Services\WalletService;
use Illuminate\Console\Command;

class VerifyLedger extends Command
{
    protected $signature = 'system:verify';

    protected $description = 'Verify wallet ledger balances and report snapshot hash chain';

    public function handle(WalletService $wallets, ReportService $reports): int
    {
        $ledger = $wallets->verify();

        $this->info("Wallets checked: {$ledger['checked']}");

        if ($ledger['issues'] === []) {
            $this->info('Ledger: OK — every balance matches its transaction history.');
        } else {
            $this->error('Ledger: '.count($ledger['issues']).' mismatch(es) found.');

            $this->table(
                ['User', 'Wallet balance', 'Ledger net', 'Difference'],
                array_map(fn ($issue) => [
                    $issue['username'] ?? $issue['user_id'],
                    number_format($issue['wallet_balance']),
                    number_format($issue['ledger_net']),
                    number_format($issue['difference']),
                ], $ledger['issues'])
            );
        }

        $chain = $reports->verifyChain();

        $this->info("Report snapshots checked: {$chain['checked']}");

        if ($chain['issues'] === []) {
            $this->info('Report chain: OK — snapshot hashes match and links are intact.');
        } else {
            foreach ($chain['issues'] as $issue) {
                $this->error('Report chain: '.$issue);
            }
        }

        return ($ledger['issues'] === [] && $chain['issues'] === []) ? self::SUCCESS : self::FAILURE;
    }
}
