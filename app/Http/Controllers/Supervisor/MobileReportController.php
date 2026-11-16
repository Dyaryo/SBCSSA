<?php

namespace App\Http\Controllers\Supervisor;

use App\Http\Controllers\Controller;
use App\Models\Bet;
use App\Models\Fight;

/**
 * Phone-friendly block report: every ten graded fights, the overall summary
 * plus the draw and cancelled totals, installable to a home screen.
 */
class MobileReportController extends Controller
{
    private const BLOCK = 10;

    public function index()
    {
        $fights = Fight::with('event')
            ->whereNotNull('result')
            ->orderBy('id')
            ->get();

        $bets = Bet::whereIn('fight_id', $fights->pluck('id'))
            ->get()
            ->groupBy('fight_id');

        $total = $fights->count();
        $blockNo = $total > 0 ? (int) ceil($total / self::BLOCK) : 1;
        $start = ($blockNo - 1) * self::BLOCK;

        $blockFights = $fights->slice($start, self::BLOCK)->values();
        $remaining = $total === 0 ? self::BLOCK : (self::BLOCK - ($total % self::BLOCK)) % self::BLOCK;

        return view('mobile.report', [
            'overall' => $this->summarise($fights, $bets),
            'block' => $this->summarise($blockFights, $bets),
            'blockFights' => $blockFights,
            'blockNo' => $blockNo,
            'blockStart' => $start + 1,
            'blockEnd' => $start + $blockFights->count(),
            'blockSize' => self::BLOCK,
            'remaining' => $remaining,
            'totalGraded' => $total,
            'liveSignature' => $this->signature($total, $bets),
        ]);
    }

    public function feed()
    {
        $ids = Fight::whereNotNull('result')->pluck('id');
        $total = $ids->count();
        $bets = Bet::whereIn('fight_id', $ids)->get()->groupBy('fight_id');

        return response()
            ->json(['signature' => $this->signature($total, $bets)])
            ->header('Cache-Control', 'no-store');
    }

    private function summarise($fights, $betsByFight): array
    {
        $ids = $fights->pluck('id')->all();
        $bets = $betsByFight->filter(fn ($rows, $fightId) => in_array($fightId, $ids, true))->flatten(1);

        return [
            'fights' => $fights->count(),
            'meron' => $fights->where('result', Fight::RESULT_MERON)->count(),
            'wala' => $fights->where('result', Fight::RESULT_WALA)->count(),
            'draw' => $fights->where('result', Fight::RESULT_DRAW)->count(),
            'cancelled' => $fights->where('result', Fight::RESULT_CANCELLED)->count(),
            'bets' => $bets->count(),
            'stake' => (int) $bets->sum('amount_credits'),
            'payout' => (int) $bets->where('status', Bet::STATUS_WON)->sum('potential_payout'),
            'draw_stake' => (int) $bets->where('side', Fight::SIDE_DRAW)->sum('amount_credits'),
            'draw_payout' => (int) $bets->where('side', Fight::SIDE_DRAW)->where('status', Bet::STATUS_WON)->sum('potential_payout'),
            'cancelled_refund' => (int) $bets->whereIn('status', [Bet::STATUS_REFUNDED, Bet::STATUS_VOID])->sum('amount_credits'),
        ];
    }

    private function signature(int $total, $betsByFight): string
    {
        $bets = $betsByFight->flatten(1);

        return md5(implode(':', [
            $total,
            (int) $bets->max('id'),
            (int) $bets->sum('amount_credits'),
            (int) $bets->where('status', Bet::STATUS_WON)->sum('potential_payout'),
            (int) $bets->where('side', Fight::SIDE_DRAW)->count(),
            Fight::where('result', Fight::RESULT_CANCELLED)->count(),
        ]));
    }
}
