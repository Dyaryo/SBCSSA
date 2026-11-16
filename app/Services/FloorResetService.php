<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Clear the operational floor — cards, fights, bets, shifts, ledger and
 * history — while keeping accounts, rosters and workstations. Used by both the
 * `system:reset` command and the Supervisor's Floor reset page.
 */
class FloorResetService
{
    /**
     * @var array<int,string>
     */
    private const TABLES = [
        'report_snapshots',
        'wallet_transactions',
        'bets',
        'shift_sessions',
        'fight_events',
        'fights',
        'cocks',
        'events',
        'audit_logs',
        'login_events',
    ];

    /**
     * @return array{counts:array<string,int>, till:int}
     */
    public function reset(int $till = 0, ?string $keepSessionId = null): array
    {
        $till = max(0, $till);
        $counts = $this->counts();

        Schema::disableForeignKeyConstraints();

        try {
            foreach (self::TABLES as $table) {
                if (Schema::hasTable($table)) {
                    DB::table($table)->truncate();
                }
            }

            if (Schema::hasTable('virtual_wallets')) {
                DB::table('virtual_wallets')->update([
                    'balance_credits' => $till,
                    'updated_at' => now(),
                ]);
            }
        } finally {
            Schema::enableForeignKeyConstraints();
        }

        // Stale sessions would land on a console with no shift and no fight.
        // The Supervisor doing the reset keeps their own session so the page
        // can confirm what happened.
        if (Schema::hasTable('sessions')) {
            DB::table('sessions')
                ->when($keepSessionId, fn ($query) => $query->where('id', '!=', $keepSessionId))
                ->delete();
        }

        return ['counts' => $counts, 'till' => $till];
    }

    /**
     * What is on the floor right now, for the confirmation screen.
     *
     * @return array<string,int>
     */
    public function counts(): array
    {
        $tables = [
            'events' => 'events',
            'fights' => 'fights',
            'bets' => 'bets',
            'shifts' => 'shift_sessions',
            'ledger' => 'wallet_transactions',
            'snapshots' => 'report_snapshots',
        ];

        $counts = [];

        foreach ($tables as $key => $table) {
            $counts[$key] = Schema::hasTable($table) ? DB::table($table)->count() : 0;
        }

        return $counts;
    }
}
