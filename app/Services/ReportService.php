<?php

namespace App\Services;

use App\Models\Bet;
use App\Models\Event;
use App\Models\Fight;
use App\Models\GameMasterProfile;
use App\Models\ReportSnapshot;
use App\Models\ShiftSession;
use App\Models\TellerProfile;
use App\Models\User;
use App\Models\VirtualWallet;
use App\Models\WalletTransaction;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Builds report payloads and stores hash-chained snapshots so a past report can
 * be proven unchanged.
 */
class ReportService
{
    public const SCOPES = [
        'fight' => 'Fight report',
        'event' => 'Event report',
        'teller_shift' => 'Teller shift report',
        'teller_daily' => 'Teller daily report',
        'gm_daily' => 'Game Master daily report',
        'system_monthly' => 'System monthly report',
    ];

    /**
     * @return array{scope:string,scope_ref_id:?int,period_start:string,period_end:string,payload:array<string,mixed>}
     */
    public function build(string $scope, array $params): array
    {
        return match ($scope) {
            'fight' => $this->fightReport((int) ($params['fight_id'] ?? 0)),
            'event' => $this->eventReport((int) ($params['event_id'] ?? 0)),
            'teller_shift' => $this->shiftReport((int) ($params['shift_id'] ?? 0)),
            'teller_daily' => $this->tellerDailyReport((int) ($params['teller_id'] ?? 0), $params['date'] ?? now()->toDateString()),
            'gm_daily' => $this->gameMasterDailyReport((int) ($params['game_master_id'] ?? 0), $params['date'] ?? now()->toDateString()),
            'system_monthly' => $this->systemMonthlyReport($params['month'] ?? now()->format('Y-m')),
            default => throw new \InvalidArgumentException('Unknown report scope: '.$scope),
        };
    }

    public function fightReport(int $fightId): array
    {
        $fight = Fight::with(['event', 'redCock', 'blueCock', 'bets.teller.user'])->findOrFail($fightId);
        $bets = $fight->bets->sortBy('id')->values();

        $rows = $bets->map(fn (Bet $bet) => [
            'bet_id' => $bet->id,
            'teller' => $bet->teller?->teller_code,
            'side' => $bet->side,
            'amount' => (int) $bet->amount_credits,
            'odds' => (float) $bet->odds_at_placement,
            'potential_payout' => (int) $bet->potential_payout,
            'status' => $bet->status,
            'placed_at' => $bet->placed_at?->toDateTimeString(),
        ])->all();

        return [
            'scope' => 'fight',
            'scope_ref_id' => $fight->id,
            'period_start' => optional($fight->scheduled_at)->toDateString() ?? now()->toDateString(),
            'period_end' => optional($fight->ended_at)->toDateString() ?? now()->toDateString(),
            'payload' => [
                'title' => 'Fight report',
                'fight' => [
                    'id' => $fight->id,
                    'label' => $fight->label(),
                    'event' => $fight->event?->name,
                    'status' => $fight->status,
                    'winner' => $fight->winner,
                    'odds_red' => (float) $fight->odds_red,
                    'odds_blue' => (float) $fight->odds_blue,
                ],
                'totals' => [
                    'bets' => $bets->count(),
                    'red_stake' => (int) $bets->where('side', 'red')->sum('amount_credits'),
                    'blue_stake' => (int) $bets->where('side', 'blue')->sum('amount_credits'),
                    'draw_stake' => (int) $bets->where('side', 'draw')->sum('amount_credits'),
                    'stake_total' => (int) $bets->sum('amount_credits'),
                    'payout_total' => (int) $bets->where('status', Bet::STATUS_WON)->sum('potential_payout'),
                    'refunded_total' => (int) $bets->whereIn('status', [Bet::STATUS_REFUNDED, Bet::STATUS_VOID])->sum('amount_credits'),
                ],
                'rows' => $rows,
            ],
        ];
    }

    public function eventReport(int $eventId): array
    {
        $event = Event::with(['gameMaster.user', 'fights.bets'])->findOrFail($eventId);

        $rows = $event->fights->map(function (Fight $fight) {
            return [
                'fight_no' => $fight->fight_no,
                'match' => $fight->label(),
                'status' => $fight->status,
                'winner' => $fight->winner,
                'bets' => $fight->bets->count(),
                'stake_total' => (int) $fight->bets->sum('amount_credits'),
                'payout_total' => (int) $fight->bets->where('status', Bet::STATUS_WON)->sum('potential_payout'),
            ];
        })->all();

        return [
            'scope' => 'event',
            'scope_ref_id' => $event->id,
            'period_start' => optional($event->starts_at)->toDateString() ?? now()->toDateString(),
            'period_end' => optional($event->ends_at)->toDateString() ?? now()->toDateString(),
            'payload' => [
                'title' => 'Event report',
                'event' => [
                    'id' => $event->id,
                    'code' => $event->code,
                    'name' => $event->name,
                    'venue' => $event->venue,
                    'status' => $event->status,
                    'type' => $event->typeLabel(),
                    'game_master' => $event->gameMaster?->displayName(),
                    // The card settings the Game Master typed on the event form.
                    'Planned fights' => $event->planned_fights,
                    'Bet stopper ratio' => $event->hasStopRatio() ? number_format((float) $event->stop_ratio, 3).'×' : 'none',
                    'Draw payout ratio' => number_format($event->drawRatio(), 3).'×',
                    'TV total bets' => (int) ($event->tv_total_bets ?? 100).'%',
                    'Bomba' => number_format((int) $event->bomba_amount).' · break '.(int) $event->bomba_breakdown.' · every '.(int) $event->bomba_interval.'s',
                    'Payout ratio display' => $event->payout_display === Event::DISPLAY_WHOLE ? 'Whole numbers' : 'With decimals',
                ],
                'totals' => [
                    'fights' => $event->fights->count(),
                    'settled' => $event->fights->where('status', Fight::STATUS_SETTLED)->count(),
                    'void' => $event->fights->where('status', Fight::STATUS_VOID)->count(),
                    'bets' => (int) $event->fights->sum(fn (Fight $f) => $f->bets->count()),
                    'stake_total' => (int) $event->fights->sum(fn (Fight $f) => $f->bets->sum('amount_credits')),
                    'draw_stake' => (int) $event->fights->sum(fn (Fight $f) => $f->bets->where('side', 'draw')->sum('amount_credits')),
                    'payout_total' => (int) $event->fights->sum(fn (Fight $f) => $f->bets->where('status', Bet::STATUS_WON)->sum('potential_payout')),
                ],
                'rows' => $rows,
            ],
        ];
    }

    public function shiftReport(int $shiftId): array
    {
        $shift = ShiftSession::with(['teller.user', 'workstation', 'bets.fight'])->findOrFail($shiftId);

        $rows = $shift->bets->sortBy('id')->map(fn (Bet $bet) => [
            'bet_id' => $bet->id,
            'fight' => $bet->fight?->label(),
            'side' => $bet->side,
            'amount' => (int) $bet->amount_credits,
            'odds' => (float) $bet->odds_at_placement,
            'status' => $bet->status,
            'placed_at' => $bet->placed_at?->toDateTimeString(),
        ])->all();

        $date = optional($shift->opened_at)->toDateString() ?? now()->toDateString();

        return [
            'scope' => 'teller_shift',
            'scope_ref_id' => $shift->id,
            'period_start' => $date,
            'period_end' => optional($shift->closed_at)->toDateString() ?? $date,
            'payload' => [
                'title' => 'Teller shift report',
                'shift' => [
                    'id' => $shift->id,
                    'teller_code' => $shift->teller?->teller_code,
                    'teller_name' => $shift->teller?->displayName(),
                    'workstation' => $shift->workstation?->code,
                    'status' => $shift->status,
                    'opened_at' => optional($shift->opened_at)->toDateTimeString(),
                    'closed_at' => optional($shift->closed_at)->toDateTimeString(),
                ],
                'totals' => [
                    'bets' => $shift->bets->count(),
                    'stake_total' => (int) $shift->bets->sum('amount_credits'),
                    'active' => $shift->bets->where('status', Bet::STATUS_ACTIVE)->count(),
                    'won' => (int) $shift->bets->where('status', Bet::STATUS_WON)->sum('potential_payout'),
                    'voided' => (int) $shift->bets->whereIn('status', [Bet::STATUS_VOID, Bet::STATUS_REFUNDED])->sum('amount_credits'),
                ],
                'rows' => $rows,
            ],
        ];
    }

    public function tellerDailyReport(int $tellerId, string $date): array
    {
        $teller = TellerProfile::with('user')->findOrFail($tellerId);
        $day = Carbon::parse($date);

        $bets = Bet::where('teller_id', $tellerId)
            ->whereBetween('placed_at', [$day->copy()->startOfDay(), $day->copy()->endOfDay()])
            ->with('fight')
            ->orderBy('id')
            ->get();

        $rows = $bets->map(fn (Bet $bet) => [
            'bet_id' => $bet->id,
            'fight' => $bet->fight?->label(),
            'side' => $bet->side,
            'amount' => (int) $bet->amount_credits,
            'status' => $bet->status,
            'placed_at' => $bet->placed_at?->toDateTimeString(),
        ])->all();

        return [
            'scope' => 'teller_daily',
            'scope_ref_id' => $teller->id,
            'period_start' => $day->toDateString(),
            'period_end' => $day->toDateString(),
            'payload' => [
                'title' => 'Teller daily report',
                'teller' => [
                    'id' => $teller->id,
                    'code' => $teller->teller_code,
                    'name' => $teller->displayName(),
                    'game_master' => $teller->gameMaster?->displayName(),
                ],
                'totals' => [
                    'bets' => $bets->count(),
                    'stake_total' => (int) $bets->sum('amount_credits'),
                    'voided' => (int) $bets->whereIn('status', [Bet::STATUS_VOID, Bet::STATUS_REFUNDED])->sum('amount_credits'),
                ],
                'rows' => $rows,
            ],
        ];
    }

    public function gameMasterDailyReport(int $gameMasterId, string $date): array
    {
        $gm = GameMasterProfile::with('user')->findOrFail($gameMasterId);
        $day = Carbon::parse($date);

        $events = Event::where('game_master_id', $gameMasterId)
            ->whereDate('created_at', $day->toDateString())
            ->get();

        $tellers = \App\Models\TellerProfile::visibleToGameMaster($gm->id)->with('user')->get();

        $bets = Bet::whereHas('teller', fn ($q) => $q->visibleToGameMaster($gameMasterId))
            ->whereBetween('placed_at', [$day->copy()->startOfDay(), $day->copy()->endOfDay()])
            ->get();

        $rows = $tellers->map(function (TellerProfile $teller) use ($bets) {
            $own = $bets->where('teller_id', $teller->id);

            return [
                'teller' => $teller->teller_code,
                'name' => $teller->displayName(),
                'bets' => $own->count(),
                'stake_total' => (int) $own->sum('amount_credits'),
            ];
        })->all();

        return [
            'scope' => 'gm_daily',
            'scope_ref_id' => $gm->id,
            'period_start' => $day->toDateString(),
            'period_end' => $day->toDateString(),
            'payload' => [
                'title' => 'Game Master daily report',
                'game_master' => [
                    'id' => $gm->id,
                    'name' => $gm->displayName(),
                ],
                'totals' => [
                    'events_created' => $events->count(),
                    'tellers' => $tellers->count(),
                    'bets' => $bets->count(),
                    'stake_total' => (int) $bets->sum('amount_credits'),
                ],
                'cards' => $events->map(fn (Event $event) => [
                    'code' => $event->code,
                    'name' => $event->name,
                    'status' => $event->status,
                    'planned_fights' => $event->planned_fights,
                    'bet_stopper' => $event->hasStopRatio() ? number_format((float) $event->stop_ratio, 3).'×' : 'none',
                    'draw_ratio' => number_format($event->drawRatio(), 3).'×',
                    'tv_share' => (int) ($event->tv_total_bets ?? 100).'%',
                    'bomba' => number_format((int) $event->bomba_amount).' · '.(int) $event->bomba_breakdown.' · '.(int) $event->bomba_interval.'s',
                ])->all(),
                'rows' => $rows,
            ],
        ];
    }

    public function systemMonthlyReport(string $month): array
    {
        $start = Carbon::parse($month.'-01')->startOfMonth();
        $end = $start->copy()->endOfMonth();

        $events = Event::whereBetween('created_at', [$start, $end])->get();
        $fights = Fight::whereBetween('created_at', [$start, $end])->get();
        $bets = Bet::whereBetween('placed_at', [$start, $end])->get();

        $creditsSeeded = (int) WalletTransaction::where('type', WalletTransaction::TYPE_SEED)
            ->whereBetween('created_at', [$start, $end])->sum('amount_credits');

        $payouts = (int) WalletTransaction::where('type', WalletTransaction::TYPE_PAYOUT)
            ->whereBetween('created_at', [$start, $end])->sum('amount_credits');

        $rows = $events->map(function (Event $event) use ($bets) {
            return [
                'event' => $event->code,
                'name' => $event->name,
                'status' => $event->status,
                'bets' => $bets->count(),
            ];
        })->all();

        return [
            'scope' => 'system_monthly',
            'scope_ref_id' => null,
            'period_start' => $start->toDateString(),
            'period_end' => $end->toDateString(),
            'payload' => [
                'title' => 'System monthly report',
                'period' => $start->format('F Y'),
                'totals' => [
                    'events' => $events->count(),
                    'fights' => $fights->count(),
                    'fights_settled' => $fights->where('status', Fight::STATUS_SETTLED)->count(),
                    'game_masters' => User::where('role', User::ROLE_GAME_MASTER)->count(),
                    'tellers' => User::where('role', User::ROLE_TELLER)->count(),
                    'bets' => $bets->count(),
                    'stake_total' => (int) $bets->sum('amount_credits'),
                    'credits_seeded' => $creditsSeeded,
                    'payout_total' => $payouts,
                ],
                'balances' => [
                    'credits_in_circulation' => (int) VirtualWallet::sum('balance_credits'),
                ],
                'rows' => $rows,
            ],
        ];
    }

    public function snapshot(array $report, ?User $actor = null): ReportSnapshot
    {
        $previous = ReportSnapshot::orderByDesc('id')->first();

        $payloadJson = json_encode($report['payload'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        $hash = hash('sha256', implode('|', [
            $report['scope'],
            (string) $report['scope_ref_id'],
            $report['period_start'],
            $report['period_end'],
            $payloadJson,
        ]));

        return ReportSnapshot::create([
            'scope' => $report['scope'],
            'scope_ref_id' => $report['scope_ref_id'],
            'period_start' => $report['period_start'],
            'period_end' => $report['period_end'],
            'payload' => $report['payload'],
            'payload_hash' => $hash,
            'prev_hash' => $previous?->payload_hash,
            'generated_by' => $actor?->id,
            'generated_at' => now(),
        ]);
    }

    /**
     * Re-hash every stored snapshot and check the chain links.
     *
     * @return array{checked:int,issues:array<int,string>}
     */
    public function verifyChain(): array
    {
        $issues = [];
        $previousHash = null;
        $checked = 0;

        foreach (ReportSnapshot::orderBy('id')->get() as $snapshot) {
            $checked++;

            $payloadJson = json_encode($snapshot->payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

            $expected = hash('sha256', implode('|', [
                $snapshot->scope,
                (string) $snapshot->scope_ref_id,
                $snapshot->period_start?->toDateString(),
                $snapshot->period_end?->toDateString(),
                $payloadJson,
            ]));

            if ($expected !== $snapshot->payload_hash) {
                $issues[] = "Snapshot #{$snapshot->id} payload does not match its hash.";
            }

            if ($snapshot->prev_hash !== $previousHash) {
                $issues[] = "Snapshot #{$snapshot->id} chain link is broken.";
            }

            $previousHash = $snapshot->payload_hash;
        }

        return ['checked' => $checked, 'issues' => $issues];
    }

    public function toCsv(array $report): string
    {
        $handle = fopen('php://temp', 'r+');

        $rows = $report['payload']['rows'] ?? [];

        if (empty($rows)) {
            fputcsv($handle, ['No records for this report']);
        } else {
            fputcsv($handle, array_keys($rows[0]));

            foreach ($rows as $row) {
                fputcsv($handle, array_values($row));
            }
        }

        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        return $csv ?: '';
    }
}
