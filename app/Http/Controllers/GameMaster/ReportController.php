<?php

namespace App\Http\Controllers\GameMaster;

use App\Http\Controllers\Controller;
use App\Models\Bet;
use App\Models\Event;
use App\Models\Fight;
use App\Models\TellerProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    /**
     * One page that answers "how did my events do": what was staked, what was
     * paid, what came back, per event and overall — plus the commission sheet
     * the operator and the arena split at the end of a card.
     */
    public function index(Request $request)
    {
        $gm = $request->user()->gameMasterProfile;
        abort_unless($gm, 403);

        $selectedEvent = $request->query('event_id')
            ? Event::where('game_master_id', $gm->id)->find($request->query('event_id'))
            : null;

        abort_if($request->query('event_id') && ! $selectedEvent, 404);

        // One card or the whole book: every figure below is scoped to the same
        // set of events, so the sheet and the tables always agree.
        $eventIds = Event::where('game_master_id', $gm->id)
            ->when($selectedEvent, fn ($q) => $q->whereKey($selectedEvent->id))
            ->pluck('id');

        $eventOptions = Event::where('game_master_id', $gm->id)
            ->orderByDesc('starts_at')
            ->get();

        $events = DB::table('bets')
            ->join('fights', 'fights.id', '=', 'bets.fight_id')
            ->join('events', 'events.id', '=', 'fights.event_id')
            ->whereIn('events.id', $eventIds)
            ->groupBy('events.id', 'events.code', 'events.name', 'events.type', 'events.starts_at')
            ->selectRaw(
                'events.id, events.code, events.name, events.type, events.starts_at,
                 COUNT(bets.id) AS bets,
                 COALESCE(SUM(bets.amount_credits), 0) AS staked,
                 COALESCE(SUM(CASE WHEN bets.status = ? THEN bets.potential_payout ELSE 0 END), 0) AS paid,
                 COALESCE(SUM(CASE WHEN bets.status IN (?, ?) THEN bets.amount_credits ELSE 0 END), 0) AS refunded',
                [Bet::STATUS_WON, Bet::STATUS_VOID, Bet::STATUS_REFUNDED]
            )
            ->orderByDesc('staked')
            ->get()
            ->map(function ($row) {
                $row->label = $row->name
                    ?: 'Regular event · '.($row->starts_at ? date('d M Y', strtotime((string) $row->starts_at)) : $row->code);
                $row->net = (int) $row->staked - (int) $row->paid - (int) $row->refunded;

                return $row;
            });

        $fights = DB::table('fights')
            ->join('events', 'events.id', '=', 'fights.event_id')
            ->leftJoin('bets', 'bets.fight_id', '=', 'fights.id')
            ->whereIn('events.id', $eventIds)
            ->groupBy('fights.id', 'fights.fight_no', 'fights.status', 'events.name', 'events.starts_at', 'events.code')
            ->selectRaw(
                'fights.id, fights.fight_no, fights.status, events.name AS event_name, events.starts_at, events.code,
                 COUNT(bets.id) AS bets,
                 COALESCE(SUM(bets.amount_credits), 0) AS staked,
                 COALESCE(SUM(CASE WHEN bets.status = ? THEN bets.potential_payout ELSE 0 END), 0) AS paid',
                [Bet::STATUS_WON]
            )
            ->orderByDesc('staked')
            ->limit(10)
            ->get()
            ->map(function ($row) {
                $row->label = $row->event_name
                    ?: 'Regular event · '.($row->starts_at ? date('d M Y', strtotime((string) $row->starts_at)) : $row->code);

                return $row;
            });

        $summary = [
            'events' => $eventIds->count(),
            'fights' => Fight::whereIn('event_id', $eventIds)->count(),
            'settled' => Fight::whereIn('event_id', $eventIds)->where('status', Fight::STATUS_SETTLED)->count(),
            'bets' => (int) $events->sum('bets'),
            'staked' => (int) $events->sum('staked'),
            'paid' => (int) $events->sum('paid'),
            'refunded' => (int) $events->sum('refunded'),
        ];
        $summary['net'] = $summary['staked'] - $summary['paid'] - $summary['refunded'];

        $sheetData = $this->sheet($gm->id, $eventIds->all(), $summary, $request, $selectedEvent);
        $sheet = $sheetData['rows'];
        $cashCounted = $sheetData['cash_on_hand'];
        $expenses = $sheetData['expenses'];

        return view('gm.report', compact(
            'summary', 'events', 'fights', 'sheet', 'cashCounted', 'expenses', 'eventOptions', 'selectedEvent'
        ));
    }

    /**
     * The end-of-card commission sheet. Every figure is virtual credits, but the
     * arithmetic follows the arena split: 5% gross commission, 80/20 between
     * operator and CBS, then what is still unclaimed and how the till counts.
     *
     * @param  array<string,int>  $summary
     * @return array{rows: array<int,array{0:string,1:string,2:string}>, cash_on_hand:int, expenses:int}
     */
    private function sheet(int $gameMasterId, array $eventIds, array $summary, Request $request, ?Event $selectedEvent): array
    {
        $tellerUserIds = TellerProfile::visibleToGameMaster($gameMasterId)->pluck('user_id');

        $moneyOnHand = (int) DB::table('virtual_wallets')
            ->whereIn('user_id', $tellerUserIds)
            ->sum('balance_credits');

        $unpaid = (int) DB::table('bets')
            ->join('fights', 'fights.id', '=', 'bets.fight_id')
            ->whereIn('fights.event_id', $eventIds)
            ->where('bets.status', Bet::STATUS_WON)
            ->whereNull('bets.paid_out_at')
            ->sum('bets.potential_payout');

        $drawWin = (int) DB::table('bets')
            ->join('fights', 'fights.id', '=', 'bets.fight_id')
            ->whereIn('fights.event_id', $eventIds)
            ->whereIn('bets.status', [Bet::STATUS_VOID, Bet::STATUS_REFUNDED])
            ->sum('bets.amount_credits');

        // Cash flow of the book: what the tills hold, plus what went back out,
        // minus what came in, is the capital that was put in. On a single card
        // the till balance is not part of that card, so only its own money is
        // counted.
        $capital = $selectedEvent
            ? max(0, $summary['staked'] - $summary['paid'] - $summary['refunded'])
            : $moneyOnHand + $summary['staked'] - $summary['paid'] - $summary['refunded'];

        $gross = (int) round($summary['staked'] * 0.05);
        $expenses = max(0, (int) $request->query('expenses', 0));
        $netComm = $gross - $expenses;

        $operator = (int) round($netComm * 0.80);
        $cbs = $netComm - $operator;

        $counted = $request->has('cash_on_hand')
            ? (int) $request->query('cash_on_hand')
            : $moneyOnHand;
        $shortOver = $counted - $moneyOnHand;

        $arena = $selectedEvent?->venue
            ?: (Event::where('game_master_id', $gameMasterId)->whereNotNull('venue')->latest('id')->value('venue') ?? '—');
        $eventName = $selectedEvent?->name ?? 'All events';

        $cr = fn (int $amount) => number_format($amount).' cr';

        $rows = [
            ['Arena name', $arena, 'The venue on your card'],
            ['Event name', $eventName, $selectedEvent ? 'The card this sheet covers' : 'All events unless filtered'],
            ['Money on hand', $cr($counted), 'Counted in the tills now'],
            ['Capital', $cr($capital), 'Credits put into the book'],
            ['Total bets', $cr($summary['staked']), 'Every stake taken'],
            ['5.0% gross comm.', $cr($gross), '5% of total bets'],
            ['Net comm', $cr($netComm), 'Gross commission less expenses'],
            ['Operator 80% sharing', $cr($operator), '80% of net commission'],
            ['CBS 20% sharing', $cr($cbs), '20% of net commission'],
            ['Total unclaimed / unpaid', $cr($unpaid), 'Winning slips not yet paid out'],
            ['Draw bets + draw win', '0 bets · '.$cr($drawWin), 'Refunded stakes on drawn or void fights'],
            ["Teller's short / over", ($shortOver >= 0 ? '+' : '−').$cr(abs($shortOver)), 'Counted against the till balance'],
        ];

        return [
            'rows' => $rows,
            'cash_on_hand' => $counted,
            'expenses' => $expenses,
        ];
    }
}
