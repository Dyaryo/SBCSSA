<?php

namespace App\Http\Controllers\Supervisor;

use App\Http\Controllers\Controller;
use App\Models\Bet;
use App\Models\Event;
use App\Models\Fight;
use App\Support\WinnersRoad;

/**
 * One screen for the supervisor on the floor: which cards are running, which
 * windows are taking money right now, and the same winners road the wall is
 * showing.
 */
class LiveController extends Controller
{
    public function index()
    {
        $cards = Event::with(['gameMaster.user'])
            ->withCount([
                'fights',
                'fights as settled_fights_count' => fn ($q) => $q->where('status', Fight::STATUS_SETTLED),
            ])
            ->where(function ($query) {
                $query->where('status', Event::STATUS_OPEN)
                    ->orWhereHas('fights', fn ($q) => $q->whereIn('status', [
                        Fight::STATUS_BETTING_OPEN,
                        Fight::STATUS_BETTING_LOCKED,
                        Fight::STATUS_LIVE,
                    ]));
            })
            ->orderByDesc('starts_at')
            ->limit(5)
            ->get();

        $openFights = Fight::with(['event.gameMaster.user'])
            ->whereIn('status', [Fight::STATUS_BETTING_OPEN, Fight::STATUS_LIVE])
            ->orderBy('event_id')
            ->orderBy('fight_no')
            ->get();

        $windows = $openFights->map(fn (Fight $fight) => (object) [
            'fight' => $fight,
            'red' => $fight->stakedCredits(Fight::SIDE_RED),
            'blue' => $fight->stakedCredits(Fight::SIDE_BLUE),
            'draw' => $fight->stakedCredits(Fight::SIDE_DRAW),
            'total' => $fight->stakedCredits(),
        ]);

        $road = WinnersRoad::build(
            Fight::where('status', Fight::STATUS_SETTLED)
                ->whereNotNull('result')
                ->orderByDesc('declared_at')
                ->limit(60)
                ->get()
                ->sortBy('declared_at'),
            3,
            24
        );

        $totals = [
            'open_windows' => $openFights->where('status', Fight::STATUS_BETTING_OPEN)->count(),
            'live_fights' => $openFights->where('status', Fight::STATUS_LIVE)->count(),
            'cards' => $cards->count(),
            'staked' => (int) $windows->sum('total'),
            'locked_sides' => $openFights->filter(
                fn (Fight $f) => $f->isSideLocked(Fight::SIDE_RED) || $f->isSideLocked(Fight::SIDE_BLUE)
            )->count(),
            'open_shifts' => \App\Models\ShiftSession::where('status', \App\Models\ShiftSession::STATUS_OPEN)->count(),
        ];

        $liveSignature = $this->signature($openFights, $cards);

        return view('supervisor.live', compact('cards', 'windows', 'road', 'totals', 'liveSignature'));
    }

    /**
     * Poll target for the live floor.
     */
    public function feed()
    {
        $openFights = Fight::whereIn('status', [Fight::STATUS_BETTING_OPEN, Fight::STATUS_LIVE])->get();
        $cards = Event::where(function ($query) {
            $query->where('status', Event::STATUS_OPEN)
                ->orWhereHas('fights', fn ($q) => $q->whereIn('status', [
                    Fight::STATUS_BETTING_OPEN,
                    Fight::STATUS_BETTING_LOCKED,
                    Fight::STATUS_LIVE,
                ]));
        })->get();

        return response()
            ->json(['signature' => $this->signature($openFights, $cards)])
            ->header('Cache-Control', 'no-store');
    }

    private function signature($openFights, $cards): string
    {
        $fights = $openFights->map(fn (Fight $fight) => implode(':', [
            $fight->id,
            $fight->status,
            (int) $fight->red_locked,
            (int) $fight->blue_locked,
            $fight->odds_red,
            $fight->odds_blue,
            $fight->stop_ratio,
            $fight->stakedCredits(),
        ]))->implode('|');

        return md5($fights.'#'.$cards->pluck('id')->implode(',').'#'.Fight::where('status', Fight::STATUS_SETTLED)->count()
            .'#'.\App\Models\ShiftSession::where('status', \App\Models\ShiftSession::STATUS_OPEN)->count());
    }
}
