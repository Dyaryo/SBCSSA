<?php

namespace App\Http\Controllers\GameMaster;

use App\Exceptions\BettingException;
use App\Http\Controllers\Controller;
use App\Models\Bet;
use App\Models\Fight;
use App\Services\BettingService;
use Illuminate\Http\Request;

class BetController extends Controller
{
    public function __construct(private BettingService $betting)
    {
    }

    public function index(Request $request)
    {
        $gm = $request->user()->gameMasterProfile;
        abort_unless($gm, 403);

        $book = Bet::query()
            ->whereHas('teller', fn ($q) => $q->visibleToGameMaster($gm->id))
            ->when($request->query('fight_id'), fn ($q, $id) => $q->where('fight_id', $id))
            ->when($request->query('status'), fn ($q, $status) => $q->where('status', $status))
            ->when($request->query('teller_id'), fn ($q, $id) => $q->where('teller_id', $id));

        $bets = (clone $book)->with(['fight.event', 'teller.user', 'shift'])
            ->orderByDesc('id')
            ->paginate(30)
            ->withQueryString();

        // The strip above the table, the same figures the paper book carried:
        // how much rode on each side over the whole filter, not just the page.
        $summary = [
            'meron' => (int) (clone $book)->where('side', Fight::SIDE_RED)->sum('amount_credits'),
            'wala' => (int) (clone $book)->where('side', Fight::SIDE_BLUE)->sum('amount_credits'),
            'draw' => (int) (clone $book)->where('side', Fight::SIDE_DRAW)->sum('amount_credits'),
            'total' => (int) (clone $book)->sum('amount_credits'),
        ];

        $fights = Fight::with('event')
            ->whereHas('event', fn ($q) => $q->where('game_master_id', $gm->id))
            ->orderByDesc('id')
            ->limit(50)
            ->get();

        return view('gm.bets', compact('bets', 'fights', 'summary'));
    }

    public function voidBet(Request $request, Bet $bet)
    {
        $gm = $request->user()->gameMasterProfile;
        // Unassigned pit tills work for whichever Game Master is on duty.
        abort_unless($gm && in_array($bet->teller?->game_master_id, [null, $gm->id], true), 403);

        $data = $request->validate([
            'reason' => ['required', 'string', 'max:200'],
        ]);

        try {
            $this->betting->voidBet($bet, $data['reason'], $request->user());
        } catch (BettingException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('status', 'Bet #'.$bet->id.' voided and stake refunded.');
    }
}
