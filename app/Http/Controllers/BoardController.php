<?php

namespace App\Http\Controllers;

use App\Models\Bet;
use App\Models\Event;
use App\Models\Fight;
use App\Support\WinnersRoad;
use Illuminate\Http\Request;

/**
 * The arena screen. Deliberately public and read-only: a TV or projector on the
 * pit wall opens /board once and never needs a login. Only money already shown
 * in the pit is displayed — no names, no accounts, no slips.
 */
class BoardController extends Controller
{
    public function index(Request $request)
    {
        $event = $this->currentEvent($request);
        $fight = $event?->fights()->reorder()->orderByDesc('fight_no')->first();
        $bigBet = $fight ? $this->latestBigBet($fight) : null;

        $settled = $event
            ? $event->fights()->reorder()->whereNotNull('result')->orderByDesc('fight_no')->get()
            : collect();

        $road = WinnersRoad::build($settled->sortBy('fight_no'), 6, 16);
        $stakes = $this->stakesFor($fight);
        $payouts = $this->payoutsFor($fight);

        return view('board', [
            'event' => $event,
            'fight' => $fight,
            'roadCells' => $road['cells'],
            'roadCols' => $road['cols'],
            'stakes' => $stakes,
            'payouts' => $payouts,
            'boardSignature' => $this->boardSignature($event, $fight, $stakes, $payouts, $settled),
            'results' => $settled->take(40),
            'counts' => [
                'meron' => $settled->where('result', Fight::RESULT_MERON)->count(),
                'wala' => $settled->where('result', Fight::RESULT_WALA)->count(),
                'draw' => $settled->where('result', Fight::RESULT_DRAW)->count(),
                'cancelled' => $settled->where('result', Fight::RESULT_CANCELLED)->count(),
            ],
            'minimum' => (int) config('pos.min_bet'),
            'drawRatio' => $fight ? $fight->drawRatio() : 7.0,
            'status' => $this->status($fight),
            'bigBetToken' => $bigBet ? $this->bigBetToken($bigBet) : '',
            'bigBetFeed' => route('board.feed', array_filter(['event_id' => $event?->id])),
            'bigBetThreshold' => (int) config('pos.big_bet_threshold'),
            'tvShare' => $event?->tv_total_bets ?? 100,
            'tvStake' => (int) round(array_sum($stakes) * (($event?->tv_total_bets ?? 100) / 100)),
        ]);
    }

    /**
     * Public poll target for the wall: the latest big slip's side only, so the
     * board can flash without exposing an amount, ticket, teller or player.
     */
    public function feed(Request $request)
    {
        $event = $this->currentEvent($request);
        $fight = $event?->fights()->reorder()->orderByDesc('fight_no')->first();
        $bet = $fight ? $this->latestBigBet($fight) : null;
        $stakes = $this->stakesFor($fight);
        $payouts = $this->payoutsFor($fight);
        $settled = $event
            ? $event->fights()->reorder()->whereNotNull('result')->orderByDesc('fight_no')->get()
            : collect();

        // An opaque token, never the slip id: the wall only needs to know that
        // a new big slip landed, and which side to flash. The signature covers
        // locks, odds, stakes and results so the wall refreshes itself quickly.
        return response()
            ->json([
                'token' => $bet ? $this->bigBetToken($bet) : null,
                'side' => $bet?->side,
                'signature' => $this->boardSignature($event, $fight, $stakes, $payouts, $settled),
            ])
            ->header('Cache-Control', 'no-store');
    }

    private function stakesFor(?Fight $fight): array
    {
        return [
            'meron' => $fight ? $fight->stakedCredits(Fight::SIDE_RED) : 0,
            'wala' => $fight ? $fight->stakedCredits(Fight::SIDE_BLUE) : 0,
            'draw' => $fight ? $fight->stakedCredits(Fight::SIDE_DRAW) : 0,
        ];
    }

    private function payoutsFor(?Fight $fight): array
    {
        return [
            'meron' => $fight ? (int) $fight->bets()->where('status', Bet::STATUS_ACTIVE)->where('side', Fight::SIDE_RED)->sum('potential_payout') : 0,
            'wala' => $fight ? (int) $fight->bets()->where('status', Bet::STATUS_ACTIVE)->where('side', Fight::SIDE_BLUE)->sum('potential_payout') : 0,
            'draw' => $fight ? (int) $fight->bets()->where('status', Bet::STATUS_ACTIVE)->where('side', Fight::SIDE_DRAW)->sum('potential_payout') : 0,
        ];
    }

    private function boardSignature(?Event $event, ?Fight $fight, array $stakes, array $payouts, $settled): string
    {
        return md5(implode(':', [
            $event?->id,
            $fight?->id,
            $fight?->status,
            (int) ($fight?->red_locked ?? 0),
            (int) ($fight?->blue_locked ?? 0),
            $fight?->odds_red,
            $fight?->odds_blue,
            $fight?->drawRatio(),
            $stakes['meron'], $stakes['wala'], $stakes['draw'],
            $payouts['meron'], $payouts['wala'], $payouts['draw'],
            $settled->count(),
            $settled->max('id'),
            $event?->tv_total_bets,
        ]));
    }

    // Prefer the card being bet on now; fall back to the newest card that
    // actually has a fight, so the wall never blanks out on an empty card.
    private function currentEvent(Request $request): ?Event
    {
        return $request->query('event_id')
            ? Event::find($request->query('event_id'))
            : (Event::where('status', Event::STATUS_OPEN)->whereHas('fights')->orderByDesc('starts_at')->first()
                ?? Event::whereHas('fights')->orderByDesc('starts_at')->first()
                ?? Event::where('status', Event::STATUS_OPEN)->orderByDesc('starts_at')->first()
                ?? Event::orderByDesc('starts_at')->first());
    }

    private function latestBigBet(Fight $fight): ?Bet
    {
        return Bet::where('fight_id', $fight->id)
            ->where('amount_credits', '>=', (int) config('pos.big_bet_threshold'))
            ->latest('id')
            ->first();
    }

    private function bigBetToken(Bet $bet): string
    {
        return substr(hash('sha256', 'board-big-bet|'.$bet->id.'|'.$bet->side.'|'.config('app.key')), 0, 24);
    }

    /**
     * @return array{label:string,class:string}
     */
    private function status(?Fight $fight): array
    {
        if (! $fight) {
            return ['label' => 'NO FIGHT', 'class' => 'idle'];
        }

        // A side lock closes only that side. Say so on the status strip, or the
        // wall still looks fully open after the pit locks Meron or Wala.
        if ($fight->status === Fight::STATUS_BETTING_OPEN) {
            if ($fight->isSideLocked(Fight::SIDE_RED) && $fight->isSideLocked(Fight::SIDE_BLUE)) {
                return ['label' => 'BETTING CLOSED', 'class' => 'closed'];
            }

            if ($fight->isSideLocked(Fight::SIDE_RED)) {
                return ['label' => 'MERON LOCKED', 'class' => 'locked'];
            }

            if ($fight->isSideLocked(Fight::SIDE_BLUE)) {
                return ['label' => 'WALA LOCKED', 'class' => 'locked'];
            }
        }

        return match ($fight->status) {
            Fight::STATUS_BETTING_OPEN => ['label' => 'BETTING OPEN', 'class' => 'open'],
            Fight::STATUS_BETTING_LOCKED, Fight::STATUS_LIVE => ['label' => 'CLOSED FIGHT', 'class' => 'closed'],
            Fight::STATUS_SETTLED => ['label' => 'RESULT IN', 'class' => 'settled'],
            Fight::STATUS_VOID, Fight::STATUS_CANCELLED => ['label' => 'CANCELLED', 'class' => 'cancelled'],
            default => ['label' => strtoupper($fight->status), 'class' => 'idle'],
        };
    }
}
