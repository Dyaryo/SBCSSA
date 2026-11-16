<?php

namespace App\Http\Controllers\Supervisor;

use App\Http\Controllers\Controller;
use App\Models\Bet;
use App\Models\Event;
use App\Models\Fight;
use App\Models\GameMasterProfile;
use App\Models\ShiftSession;
use App\Models\TellerProfile;
use App\Models\User;
use App\Models\VirtualWallet;
use App\Models\Workstation;
use Illuminate\Http\Request;

class OverviewController extends Controller
{
    public function index()
    {
        $totals = [
            'events' => Event::count(),
            'fights' => Fight::count(),
            'fights_settled' => Fight::where('status', Fight::STATUS_SETTLED)->count(),
            'game_masters' => GameMasterProfile::count(),
            'tellers' => TellerProfile::count(),
            'active_pos' => ShiftSession::where('status', ShiftSession::STATUS_OPEN)->count(),
            'bets' => Bet::count(),
            'credits_in_circulation' => (int) VirtualWallet::sum('balance_credits'),
            'stake_total' => (int) Bet::sum('amount_credits'),
        ];

        $tellerActivity = TellerProfile::with(['user', 'gameMaster.user'])
            ->withSum('bets as stake_total', 'amount_credits')
            ->withCount('bets')
            ->orderByDesc('stake_total')
            ->limit(10)
            ->get();

        $openShifts = ShiftSession::with(['teller.user', 'workstation'])
            ->where('status', ShiftSession::STATUS_OPEN)
            ->latest('opened_at')
            ->limit(10)
            ->get();

        $recentBets = Bet::with(['fight.event', 'teller.user'])
            ->latest('placed_at')
            ->limit(10)
            ->get();

        // Every card the Game Masters have on the floor, with the settings they
        // set on the event form: the same figures the pit and the TV board use.
        $cards = Event::with(['gameMaster.user', 'fights.bets'])
            ->orderByDesc('starts_at')
            ->limit(8)
            ->get()
            ->map(function (Event $event) {
                $event->setAttribute('stake_total', (int) $event->fights->sum(
                    fn (Fight $fight) => $fight->bets->sum('amount_credits')
                ));

                return $event;
            });

        $listeners = [
            'supervisors' => User::where('role', User::ROLE_SUPERVISOR)->count(),
            'workstations' => Workstation::count(),
            'approved_workstations' => Workstation::whereNotNull('approved_at')->count(),
        ];

        // Tills that need money before the next event. Suspended terminals are
        // left out: they cannot take bets until they are reactivated.
        $lowTillThreshold = (int) config('pos.low_till_threshold');

        $lowTills = TellerProfile::with(['user.wallet', 'gameMaster.user', 'workstation'])
            ->where('status', 'active')
            ->get()
            ->map(fn (TellerProfile $teller) => [
                'teller' => $teller,
                'balance' => (int) ($teller->user?->wallet?->balance_credits ?? 0),
            ])
            ->filter(fn (array $row) => $row['balance'] < $lowTillThreshold)
            ->sortBy('balance')
            ->values();

        // Bets voided by a teller are separate from a fight-wide refund. The
        // supervisor gets the reason and the person who cancelled it.
        $tellerCancellationBase = Bet::query()
            ->where('status', Bet::STATUS_VOID)
            ->whereHas('voidedBy', fn ($q) => $q->where('role', User::ROLE_TELLER));

        $tellerCancellations = (clone $tellerCancellationBase)
            ->with(['fight.event', 'teller.user', 'voidedBy'])
            ->latest('settled_at')
            ->limit(10)
            ->get();

        $tellerCancellationsToday = (clone $tellerCancellationBase)
            ->whereDate('settled_at', now()->toDateString())
            ->count();

        $tellerCancellationCreditsToday = (int) (clone $tellerCancellationBase)
            ->whereDate('settled_at', now()->toDateString())
            ->sum('amount_credits');

        $liveSignature = $this->signature();

        return view('supervisor.dashboard', compact(
            'totals', 'tellerActivity', 'openShifts', 'recentBets', 'listeners', 'lowTills', 'lowTillThreshold', 'cards',
            'tellerCancellations', 'tellerCancellationsToday', 'tellerCancellationCreditsToday', 'liveSignature'
        ));
    }

    /**
     * Poll target for the overview: bets, shifts, cards and money in play.
     */
    public function feed()
    {
        return response()
            ->json(['signature' => $this->signature()])
            ->header('Cache-Control', 'no-store');
    }

    private function signature(): string
    {
        return md5(implode(':', [
            Bet::count(),
            Bet::where('status', Bet::STATUS_VOID)->count(),
            (int) Bet::sum('amount_credits'),
            (int) Bet::max('id'),
            ShiftSession::where('status', ShiftSession::STATUS_OPEN)->count(),
            Event::count(),
            Fight::count(),
            (int) VirtualWallet::sum('balance_credits'),
        ]));
    }
}
