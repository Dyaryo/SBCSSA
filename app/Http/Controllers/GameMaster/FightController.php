<?php

namespace App\Http\Controllers\GameMaster;

use App\Http\Controllers\Controller;
use App\Exceptions\BettingException;
use App\Models\Bet;
use App\Models\Event;
use App\Models\Fight;
use App\Models\FightEvent as FightLog;
use App\Services\AuditService;
use App\Services\BettingService;
use App\Support\WinnersRoad;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FightController extends Controller
{
    public function __construct(
        private BettingService $betting,
        private AuditService $audit
    ) {
    }

    public function index(Request $request)
    {
        $gm = $request->user()->gameMasterProfile;
        abort_unless($gm, 403);

        $events = Event::where('game_master_id', $gm->id)->orderByDesc('created_at')->get();

        $fights = Fight::with(['event'])
            ->whereHas('event', fn ($q) => $q->where('game_master_id', $gm->id))
            ->when($request->query('event_id'), fn ($q, $id) => $q->where('event_id', $id))
            ->when($request->query('status'), fn ($q, $status) => $q->where('status', $status))
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return view('gm.fights', compact('events', 'fights'));
    }

    public function store(Request $request)
    {
        $gm = $request->user()->gameMasterProfile;
        abort_unless($gm, 403);

        $data = $request->validate([
            'event_id' => ['required', 'integer', 'exists:events,id'],
            'fight_no' => ['required', 'integer', 'min:1', 'max:9999'],
            'scheduled_at' => ['nullable', 'date'],
            'odds_red' => ['required', 'numeric', 'min:1.01', 'max:20'],
            'odds_blue' => ['required', 'numeric', 'min:1.01', 'max:20'],
        ]);

        $event = Event::findOrFail($data['event_id']);

        if ($event->game_master_id !== $gm->id) {
            abort(403, 'That event belongs to another Game Master.');
        }

        if (Fight::where('event_id', $event->id)->where('fight_no', $data['fight_no'])->exists()) {
            return back()->withErrors(['fight_no' => 'That fight number already exists in this event.'])->withInput();
        }

        // No running ahead: the previous fight must have its result before the
        // next one exists, so a half-graded event can never stack up bouts.
        $latest = $event->fights()->reorder()->orderByDesc('fight_no')->first();

        if ($latest && ! $latest->isTerminal()) {
            return back()->withErrors([
                'fight_no' => 'Fight #'.$latest->fight_no.' has no declared winning side yet. Grade it before creating fight #'.$data['fight_no'].'.',
            ])->withInput();
        }

        // Corners are only ever Meron and Wala; no fighter has to be chosen.
        // The payout cap is the event's stop bet ratio, so every fight in the
        // event inherits it without the pit retyping it.
        $fight = Fight::create([
            'event_id' => $event->id,
            'fight_no' => $data['fight_no'],
            'red_cock_id' => null,
            'blue_cock_id' => null,
            'scheduled_at' => $data['scheduled_at'] ?? null,
            'odds_red' => $data['odds_red'],
            'odds_blue' => $data['odds_blue'],
            'stop_ratio' => $event->stop_ratio,
            'status' => Fight::STATUS_SCHEDULED,
        ]);

        FightLog::create([
            'fight_id' => $fight->id,
            'kind' => 'created',
            'note' => 'Fight scheduled.',
            'created_by' => $request->user()->id,
            'created_at' => now(),
        ]);

        $this->audit->log('fight.created', $fight, $request->user(), [], [
            'fight_no' => $fight->fight_no,
            'event_id' => $fight->event_id,
        ]);

        return back()->with('status', 'Fight #'.$fight->fight_no.' scheduled for '.$event->name.'.');
    }

    public function show(Request $request, Fight $fight)
    {
        $gm = $request->user()->gameMasterProfile;
        abort_unless($gm && $fight->event?->game_master_id === $gm->id, 403);

        $fight->load(['event', 'declaredBy', 'logs']);

        $bets = $fight->bets()->with('teller.user')->orderByDesc('id')->get();

        $totals = [
            'red' => (int) $bets->where('side', 'red')->sum('amount_credits'),
            'blue' => (int) $bets->where('side', 'blue')->sum('amount_credits'),
            'draw' => (int) $bets->where('side', 'draw')->sum('amount_credits'),
            'count' => $bets->count(),
            'payout' => (int) $bets->where('status', 'won')->sum('potential_payout'),
            'refunded' => (int) $bets->whereIn('status', ['void', 'refunded'])->sum('amount_credits'),
        ];

        $live = $bets->where('status', 'active');

        // What each grading decision would move, shown on the buttons before
        // the Game Master commits it.
        $preview = [
            'meron' => (int) $live->where('side', 'red')->sum('potential_payout'),
            'wala' => (int) $live->where('side', 'blue')->sum('potential_payout'),
            'draw' => (int) $live->where('side', 'draw')->sum('potential_payout'),
            'refund' => (int) $live->sum('amount_credits'),
        ];

        $nextFightNo = (int) Fight::where('event_id', $fight->event_id)->max('fight_no') + 1;

        // The same TV box the arena board shows, so the operator sees exactly
        // what the wall is exposing for this fight.
        $tvShare = (int) ($fight->event?->tv_total_bets ?? 100);
        $tvPool = (int) $bets->whereIn('status', [Bet::STATUS_ACTIVE, Bet::STATUS_WON, Bet::STATUS_LOST])->sum('amount_credits');
        $tvStake = (int) round($tvPool * $tvShare / 100);

        // Once winnings have been handed over the counter, a declared result
        // can no longer be reversed.
        $regradeBlocked = $fight->bets()->whereNotNull('paid_out_at')->exists();
        $windowSignature = $this->windowSignature($fight);

        return view('gm.fight', compact(
            'fight', 'bets', 'totals', 'preview', 'nextFightNo', 'tvShare', 'tvPool', 'tvStake',
            'regradeBlocked', 'windowSignature'
        ));
    }

    /**
     * Poll target for the fight console: the pit sees tellers' money arrive
     * without touching refresh, and the page reloads itself when it changes.
     */
    public function feed(Request $request, Fight $fight)
    {
        $gm = $request->user()->gameMasterProfile;
        abort_unless($gm && $fight->event?->game_master_id === $gm->id, 403);

        return response()
            ->json(['signature' => $this->windowSignature($fight)])
            ->header('Cache-Control', 'no-store');
    }

    private function windowSignature(Fight $fight): string
    {
        $bets = $fight->bets()->get();

        return md5(implode(':', [
            $fight->id,
            $fight->status,
            (int) $fight->red_locked,
            (int) $fight->blue_locked,
            $fight->odds_red,
            $fight->odds_blue,
            $fight->stop_ratio,
            $bets->count(),
            (int) $bets->where('amount_credits', '>', 0)->sum('amount_credits'),
            (int) $bets->where('status', Bet::STATUS_ACTIVE)->sum('potential_payout'),
            (string) $fight->result,
        ]));
    }

    public function openBetting(Request $request, Fight $fight)
    {
        return $this->act($request, $fight, fn () => $this->betting->openBetting($fight, $request->user()), 'Betting opened.');
    }

    public function lockBetting(Request $request, Fight $fight)
    {
        return $this->act($request, $fight, fn () => $this->betting->lockBetting($fight, $request->user()), 'Betting locked.');
    }

    public function start(Request $request, Fight $fight)
    {
        return $this->act($request, $fight, fn () => $this->betting->startFight($fight, $request->user()), 'Fight is now live.');
    }

    /**
     * Shut or reopen one corner of a fight when betting runs one-sided.
     */
    public function sideLock(Request $request, Fight $fight)
    {
        $data = $request->validate([
            'side' => ['required', 'in:red,blue'],
            'locked' => ['required', 'boolean'],
        ]);

        $locked = (bool) $data['locked'];

        return $this->act(
            $request,
            $fight,
            fn () => $this->betting->setSideLock($fight, $data['side'], $locked, $request->user()),
            Fight::sideTitle($data['side']).($locked ? ' locked.' : ' unlocked.')
        );
    }

    public function settle(Request $request, Fight $fight)
    {
        $data = $request->validate([
            'winner' => ['required', 'in:red,blue'],
        ]);

        return $this->act(
            $request,
            $fight,
            fn () => $this->betting->settle($fight, $data['winner'], $request->user()),
            'Result declared for the '.$data['winner'].' side.'
        );
    }

    public function void(Request $request, Fight $fight)
    {
        $data = $request->validate([
            'reason' => ['required', 'string', 'max:200'],
        ]);

        return $this->act(
            $request,
            $fight,
            fn () => $this->betting->voidFight($fight, $data['reason'], $request->user()),
            'Fight voided and stakes refunded.'
        );
    }

    /**
     * Grade the fight: meron, wala, draw or cancelled.
     */
    public function result(Request $request, Fight $fight)
    {
        $data = $request->validate([
            'result' => ['required', 'in:meron,wala,draw,cancelled'],
            'reason' => ['required_if:result,cancelled', 'nullable', 'string', 'max:200'],
        ]);

        $label = strtoupper($data['result']);

        return $this->act(
            $request,
            $fight,
            fn () => $this->betting->declareResult($fight, $data['result'], $request->user(), $data['reason'] ?? null),
            'Result recorded: '.$label.'. Payouts and refunds are already in the ledger.'
        );
    }

    /**
     * Correct a declared result after the fact. Mistakes happen; the money is
     * reversed and the fight is graded again with the right side.
     */
    public function regrade(Request $request, Fight $fight)
    {
        $data = $request->validate([
            'result' => ['required', 'in:meron,wala,draw,cancelled'],
            'reason' => ['required', 'string', 'max:200'],
        ]);

        return $this->act(
            $request,
            $fight,
            fn () => $this->betting->regrade($fight, $data['result'], $request->user(), $data['reason']),
            'Result corrected to '.strtoupper($data['result']).'. Wallets and slips were adjusted.'
        );
    }

    /**
     * Create the next fight of the same event after this one is finalised, so
     * the pit can carry on without going back to the fight list.
     */
    public function next(Request $request, Fight $fight)
    {
        $gm = $request->user()->gameMasterProfile;
        abort_unless($gm && $fight->event?->game_master_id === $gm->id, 403);

        $data = $request->validate([
            'odds_red' => ['required', 'numeric', 'min:1.01', 'max:20'],
            'odds_blue' => ['required', 'numeric', 'min:1.01', 'max:20'],
            'scheduled_at' => ['nullable', 'date'],
        ]);

        if (! $fight->isTerminal()) {
            return back()->with('error', 'Grade this fight first — then the next one can be created.');
        }

        $fightNo = (int) Fight::where('event_id', $fight->event_id)->max('fight_no') + 1;

        $next = Fight::create([
            'event_id' => $fight->event_id,
            'fight_no' => $fightNo,
            'red_cock_id' => null,
            'blue_cock_id' => null,
            'scheduled_at' => $data['scheduled_at'] ?? null,
            'odds_red' => $data['odds_red'],
            'odds_blue' => $data['odds_blue'],
            'stop_ratio' => $fight->event?->stop_ratio,
            'status' => Fight::STATUS_SCHEDULED,
        ]);

        FightLog::create([
            'fight_id' => $next->id,
            'kind' => 'created',
            'note' => 'Fight scheduled after '.$fight->label().'.',
            'created_by' => $request->user()->id,
            'created_at' => now(),
        ]);

        $this->audit->log('fight.created', $next, $request->user(), [], [
            'fight_no' => $next->fight_no,
            'event_id' => $next->event_id,
            'after_fight_id' => $fight->id,
        ]);

        return redirect()->route('gm.fights.show', $next)
            ->with('status', 'Fight #'.$next->fight_no.' created. Open betting when the handlers are ready.');
    }

    /**
     * The paper fight book: each fight with what was riding on each side and
     * what each side would pay, the way the old hand-written sheet read.
     */
    public function fightHistory()
    {
        $gm = request()->user()->gameMasterProfile;
        abort_unless($gm, 403);

        $fights = DB::table('fights')
            ->join('events', 'events.id', '=', 'fights.event_id')
            ->leftJoin('bets', 'bets.fight_id', '=', 'fights.id')
            ->where('events.game_master_id', $gm->id)
            ->groupBy('fights.id', 'fights.fight_no', 'fights.created_at', 'fights.status', 'fights.result', 'fights.winner', 'events.name', 'events.starts_at', 'events.code')
            ->selectRaw(
                'fights.id, fights.fight_no, fights.created_at, fights.status, fights.result, fights.winner,
                 events.name AS event_name, events.starts_at, events.code,
                 COALESCE(SUM(CASE WHEN bets.side = ? THEN bets.amount_credits ELSE 0 END), 0) AS meron_value,
                 COALESCE(SUM(CASE WHEN bets.side = ? THEN bets.potential_payout ELSE 0 END), 0) AS meron_payout,
                 COALESCE(SUM(CASE WHEN bets.side = ? THEN bets.amount_credits ELSE 0 END), 0) AS wala_value,
                 COALESCE(SUM(CASE WHEN bets.side = ? THEN bets.potential_payout ELSE 0 END), 0) AS wala_payout,
                 COALESCE(SUM(bets.amount_credits), 0) AS draw_value,
                 COALESCE(SUM(bets.amount_credits), 0) AS draw_payout',
                [Fight::SIDE_RED, Fight::SIDE_RED, Fight::SIDE_BLUE, Fight::SIDE_BLUE]
            )
            ->orderByDesc('fights.id')
            ->paginate(50)
            ->withQueryString();

        $fights->getCollection()->transform(function ($row) {
            $row->event_label = $row->event_name
                ?: 'Regular event · '.($row->starts_at ? date('d M Y', strtotime((string) $row->starts_at)) : $row->code);

            return $row;
        });

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

        return view('gm.fight-history-index', compact('fights', 'road'));
    }

    /**
     * Every slip on the fight, including voided and paid ones, with who took it
     * and when it was handed over.
     */
    public function history(Request $request, Fight $fight)
    {
        $gm = $request->user()->gameMasterProfile;
        abort_unless($gm && $fight->event?->game_master_id === $gm->id, 403);

        $bets = $fight->bets()
            ->with(['teller.user', 'voidedBy', 'paidOutBy'])
            ->orderBy('id')
            ->get();

        $totals = [
            'count' => $bets->count(),
            'meron' => (int) $bets->where('side', Fight::SIDE_RED)->sum('amount_credits'),
            'wala' => (int) $bets->where('side', Fight::SIDE_BLUE)->sum('amount_credits'),
            'paid' => (int) $bets->where('status', 'won')->sum('potential_payout'),
            'refunded' => (int) $bets->whereIn('status', ['void', 'refunded'])->sum('amount_credits'),
        ];

        return view('gm.fight-history', compact('fight', 'bets', 'totals'));
    }

    private function act(Request $request, Fight $fight, callable $action, string $message)
    {
        $gm = $request->user()->gameMasterProfile;
        abort_unless($gm && $fight->event?->game_master_id === $gm->id, 403);

        try {
            $action();
        } catch (BettingException $e) {
            return back()->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            report($e);

            return back()->with('error', 'The action could not be completed. Check the audit log.');
        }

        return back()->with('status', $message);
    }
}
