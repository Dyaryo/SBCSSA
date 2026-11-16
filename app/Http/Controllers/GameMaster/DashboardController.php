<?php

namespace App\Http\Controllers\GameMaster;

use App\Http\Controllers\Controller;
use App\Models\Bet;
use App\Models\Event;
use App\Models\Fight;
use App\Models\ShiftSession;
use App\Models\TellerProfile;
use App\Models\WalletTransaction;
use App\Support\WinnersRoad;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $gm = $request->user()->gameMasterProfile;
        abort_unless($gm, 403, 'No Game Master profile is linked to this account.');

        $totals = [
            'events' => Event::where('game_master_id', $gm->id)->count(),
            'open_events' => Event::where('game_master_id', $gm->id)->where('status', Event::STATUS_OPEN)->count(),
            'fights' => Fight::whereHas('event', fn ($q) => $q->where('game_master_id', $gm->id))->count(),
            'live_or_open' => Fight::whereHas('event', fn ($q) => $q->where('game_master_id', $gm->id))
                ->whereIn('status', [Fight::STATUS_BETTING_OPEN, Fight::STATUS_LIVE, Fight::STATUS_BETTING_LOCKED])
                ->count(),
            'tellers' => \App\Models\TellerProfile::visibleToGameMaster($gm->id)->count(),
            'open_shifts' => ShiftSession::whereHas('teller', fn ($q) => $q->visibleToGameMaster($gm->id))
                ->where('status', ShiftSession::STATUS_OPEN)
                ->count(),
            'bets' => Bet::whereHas('teller', fn ($q) => $q->visibleToGameMaster($gm->id))->count(),
            'stake_total' => (int) Bet::whereHas('teller', fn ($q) => $q->visibleToGameMaster($gm->id))->sum('amount_credits'),
            'betting_open' => Fight::whereHas('event', fn ($q) => $q->where('game_master_id', $gm->id))
                ->where('status', Fight::STATUS_BETTING_OPEN)
                ->count(),
            'live' => Fight::whereHas('event', fn ($q) => $q->where('game_master_id', $gm->id))
                ->where('status', Fight::STATUS_LIVE)
                ->count(),
            'awaiting_grade' => Fight::whereHas('event', fn ($q) => $q->where('game_master_id', $gm->id))
                ->whereIn('status', [Fight::STATUS_BETTING_LOCKED, Fight::STATUS_LIVE])
                ->count(),
            'settled' => Fight::whereHas('event', fn ($q) => $q->where('game_master_id', $gm->id))
                ->whereNotNull('result')
                ->count(),
        ];

        $todayBets = Bet::whereHas('teller', fn ($q) => $q->visibleToGameMaster($gm->id))
            ->whereDate('placed_at', now()->toDateString())
            ->count();

        $activeFights = Fight::with(['event', 'redCock', 'blueCock'])
            ->whereHas('event', fn ($q) => $q->where('game_master_id', $gm->id))
            ->whereIn('status', [Fight::STATUS_SCHEDULED, Fight::STATUS_BETTING_OPEN, Fight::STATUS_BETTING_LOCKED, Fight::STATUS_LIVE])
            ->orderBy('fight_no')
            ->get();

        $tellers = TellerProfile::with(['user', 'workstation'])
            ->where('game_master_id', $gm->id)
            ->orderBy('teller_code')
            ->get();

        $recentBets = Bet::with(['fight', 'teller.user'])
            ->whereHas('teller', fn ($q) => $q->visibleToGameMaster($gm->id))
            ->latest('placed_at')
            ->limit(10)
            ->get();

        $recentResults = Fight::with(['event', 'redCock', 'blueCock'])
            ->whereHas('event', fn ($q) => $q->where('game_master_id', $gm->id))
            ->whereNotNull('result')
            ->latest('declared_at')
            ->limit(6)
            ->get();

        // The pit's bead plate: the last graded fights, oldest first.
        $road = WinnersRoad::build(
            Fight::whereHas('event', fn ($q) => $q->where('game_master_id', $gm->id))
                ->whereNotNull('result')
                ->orderByDesc('declared_at')
                ->limit(60)
                ->get()
                ->sortBy('declared_at'),
            3,
            20
        );

        $liveSignature = $this->signature($gm->id);

        return view('gm.dashboard', compact(
            'gm', 'totals', 'todayBets', 'activeFights', 'tellers', 'recentBets', 'recentResults',
            'road', 'liveSignature'
        ));
    }

    /**
     * Poll target for the pit dashboard.
     */
    public function feed(Request $request)
    {
        $gm = $request->user()->gameMasterProfile;
        abort_unless($gm, 403);

        return response()
            ->json(['signature' => $this->signature($gm->id)])
            ->header('Cache-Control', 'no-store');
    }

    private function signature(int $gameMasterId): string
    {
        $bets = Bet::whereHas('teller', fn ($q) => $q->visibleToGameMaster($gameMasterId));
        $fights = Fight::whereHas('event', fn ($q) => $q->where('game_master_id', $gameMasterId));

        return md5(implode(':', [
            (clone $bets)->count(),
            (int) (clone $bets)->sum('amount_credits'),
            (int) (clone $bets)->max('id'),
            (clone $fights)->whereIn('status', [
                Fight::STATUS_BETTING_OPEN,
                Fight::STATUS_BETTING_LOCKED,
                Fight::STATUS_LIVE,
            ])->count(),
            (clone $fights)->whereNotNull('result')->count(),
            ShiftSession::whereHas('teller', fn ($q) => $q->visibleToGameMaster($gameMasterId))
                ->where('status', ShiftSession::STATUS_OPEN)->count(),
        ]));
    }
}
