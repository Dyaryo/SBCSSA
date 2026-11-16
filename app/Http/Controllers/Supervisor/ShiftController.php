<?php

namespace App\Http\Controllers\Supervisor;

use App\Http\Controllers\Controller;
use App\Models\Bet;
use App\Models\ShiftSession;
use App\Services\AuditService;
use App\Services\ReportService;
use App\Services\ShiftSlipService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * POS shift oversight for the Supervisor: look at every open till and force a
 * shift closed when a teller has walked away or a terminal died.
 */
class ShiftController extends Controller
{
    public function __construct(
        private ReportService $reports,
        private AuditService $audit,
        private ShiftSlipService $slips
    ) {
    }

    /**
     * Read-only closeout slip, so the Supervisor can print the same paper the
     * terminal prints without holding a POS login.
     */
    public function closeout(ShiftSession $shift)
    {
        return view('pos.closeout', ['slip' => $this->slips->forShift($shift)]);
    }

    public function index(Request $request)
    {
        $open = ShiftSession::with(['teller.user', 'teller.gameMaster.user', 'workstation'])
            ->where('status', ShiftSession::STATUS_OPEN)
            ->withCount([
                'bets',
                'bets as unsettled_count' => fn ($q) => $q->where('status', 'active'),
            ])
            ->withSum('bets as stake_total', 'amount_credits')
            ->orderBy('opened_at')
            ->get();

        $recent = ShiftSession::with(['teller.user', 'workstation'])
            ->whereIn('status', [ShiftSession::STATUS_CLOSED, ShiftSession::STATUS_FORCE_CLOSED])
            ->latest('closed_at')
            ->limit(15)
            ->get();

        // What each open till still owes in unsettled bets, so a forced close
        // cannot quietly bury live money.
        $owedByShift = Bet::where('status', Bet::STATUS_ACTIVE)
            ->whereIn('shift_session_id', $open->pluck('id'))
            ->selectRaw('shift_session_id, COUNT(*) as bets, SUM(amount_credits) as stake, SUM(potential_payout) as payout')
            ->groupBy('shift_session_id')
            ->get()
            ->keyBy('shift_session_id');

        $owedTotal = [
            'bets' => (int) $owedByShift->sum('bets'),
            'stake' => (int) $owedByShift->sum('stake'),
            'payout' => (int) $owedByShift->sum('payout'),
        ];

        // Which fights each open till is still waiting on, so the close button
        // can name them instead of asking an abstract "are you sure?".
        $pendingFights = Bet::with(['fight.redCock', 'fight.blueCock'])
            ->where('status', Bet::STATUS_ACTIVE)
            ->whereIn('shift_session_id', $open->pluck('id'))
            ->get()
            ->groupBy('shift_session_id')
            ->map(fn ($bets) => $bets->pluck('fight')->filter()->unique('id')->values());

        return view('supervisor.shifts', compact('open', 'recent', 'owedByShift', 'owedTotal', 'pendingFights'));
    }

    /**
     * One shift in detail: every bet, and the unsettled ones called out so the
     * money still owed stays visible even after a forced close.
     */
    public function show(ShiftSession $shift)
    {
        $shift->load(['teller.user', 'teller.gameMaster.user', 'workstation']);

        $bets = $shift->bets()
            ->with(['fight.event', 'fight.redCock', 'fight.blueCock'])
            ->orderBy('id')
            ->get();

        $unsettled = $bets->where('status', Bet::STATUS_ACTIVE);
        $won = $bets->where('status', Bet::STATUS_WON);

        $pendingFights = $unsettled->groupBy('fight_id')
            ->map(fn ($group) => $group->first()->fight)
            ->filter()
            ->values();

        $totals = [
            'count' => $bets->count(),
            'stake' => (int) $bets->sum('amount_credits'),
            'unsettled_count' => $unsettled->count(),
            'unsettled_stake' => (int) $unsettled->sum('amount_credits'),
            'unsettled_payout' => (int) $unsettled->sum('potential_payout'),
            'won_count' => $won->count(),
            'won_payout' => (int) $won->sum('potential_payout'),
            'paid_out' => (int) $bets->whereNotNull('paid_out_at')->sum('potential_payout'),
            'owed_at_window' => $won->whereNull('paid_out_at')->sum('potential_payout'),
            'refunded' => (int) $bets->whereIn('status', [Bet::STATUS_VOID, Bet::STATUS_REFUNDED])->sum('amount_credits'),
        ];

        return view('supervisor.shift', compact('shift', 'bets', 'unsettled', 'totals', 'pendingFights'));
    }

    public function close(Request $request, ShiftSession $shift)
    {
        if (! $shift->isOpen()) {
            return back()->with('error', 'That shift is already closed.');
        }

        $data = $request->validate([
            'reason' => ['required', 'string', 'max:200'],
        ]);

        DB::transaction(function () use ($shift, $data, $request) {
            $shift->load('bets');

            $shift->total_bets_count = $shift->bets->count();
            $shift->total_amount_credits = (int) $shift->bets->sum('amount_credits');
            $shift->status = ShiftSession::STATUS_FORCE_CLOSED;
            $shift->closed_at = now();
            $shift->closed_reason = $data['reason'];
            $shift->save();

            $unsettled = $shift->bets->where('status', Bet::STATUS_ACTIVE);

            $this->audit->log('shift.force_closed', $shift, $request->user(), [], [
                'reason' => $data['reason'],
                'unsettled_bets' => $unsettled->count(),
                'unsettled_stake' => (int) $unsettled->sum('amount_credits'),
            ]);

            $this->reports->snapshot(
                $this->reports->build('teller_shift', ['shift_id' => $shift->id]),
                $request->user()
            );
        });

        return back()->with('status', 'Shift #'.$shift->id.' force-closed and sealed into a report snapshot.');
    }
}
