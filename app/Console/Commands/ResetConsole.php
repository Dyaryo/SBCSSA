<?php

namespace App\Console\Commands;

use App\Services\FloorResetService;
use Illuminate\Console\Command;

class ResetConsole extends Command
{
    protected $signature = 'system:reset
                            {--force : Run without asking for confirmation}
                            {--till=0 : Credits left in every POS till afterwards}';

    protected $description = 'Clear all events, fights, bets, shifts and ledger history so the console starts empty. Logins are kept.';

    public function handle(FloorResetService $reset): int
    {
        $till = max(0, (int) $this->option('till'));

        if (! $this->option('force')
            && ! $this->confirm('Delete every event, fight, bet, shift and ledger row? Accounts and logins are kept.', false)) {
            $this->warn('Nothing was deleted.');

            return self::SUCCESS;
        }

        $reset->reset($till);

        $this->info('Console reset: no events, fights, bets, shifts or ledger rows.');
        $this->line('Accounts and logins kept. Every till now holds '.number_format($till).' credits.');

        return self::SUCCESS;
    }
}
