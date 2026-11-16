<?php

namespace App\Http\Controllers\GameMaster;

use App\Http\Controllers\Controller;
use App\Models\Bet;
use App\Models\Event;
use App\Models\Fight;
use App\Services\AuditService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class EventController extends Controller
{
    public function __construct(private AuditService $audit)
    {
    }

    public function index(Request $request)
    {
        $gm = $request->user()->gameMasterProfile;
        abort_unless($gm, 403);

        $events = Event::withCount('fights')
            ->where('game_master_id', $gm->id)
            ->orderByDesc('created_at')
            ->get();

        return view('gm.events', compact('events'));
    }

    public function store(Request $request)
    {
        $gm = $request->user()->gameMasterProfile;
        abort_unless($gm, 403);

        $data = $request->validate([
            'type' => ['required', 'in:regular,derby'],
            'name' => ['required', 'string', 'max:120'],
            'venue' => ['nullable', 'string', 'max:120'],
            'starts_at' => ['nullable', 'date'],
            'stop_ratio' => ['nullable', 'numeric', 'min:1.01', 'max:20'],
            'draw_ratio' => ['nullable', 'numeric', 'min:1', 'max:100'],
            'planned_fights' => ['nullable', 'integer', 'min:1', 'max:500'],
            'bomba_amount' => ['nullable', 'integer', 'min:0', 'max:100000000'],
            'bomba_breakdown' => ['nullable', 'integer', 'min:0', 'max:100'],
            'bomba_interval' => ['nullable', 'integer', 'min:0', 'max:3600'],
            'payout_display' => ['nullable', 'in:decimals,whole'],
            'tv_total_bets' => ['nullable', 'in:100,70,50'],
            'entries' => ['required_if:type,derby', 'nullable', 'string', 'max:2000'],
        ]);

        $derby = $data['type'] === Event::TYPE_DERBY;

        $entries = collect(preg_split('/\r\n|\r|\n/', (string) ($data['entries'] ?? '')))
            ->map(fn ($entry) => trim((string) $entry))
            ->filter()
            ->values()
            ->all();

        if ($derby && count($entries) < 2) {
            return back()->withErrors(['entries' => 'A derby needs at least two entry names, one per line.'])->withInput();
        }

        $event = Event::create([
            'code' => $this->nextCode($data['type']),
            // Every event carries the title the operator typed; a derby also
            // lists the entry names.
            'name' => $data['name'],
            'type' => $data['type'],
            'entries' => $derby ? $entries : null,
            'venue' => $data['venue'] ?? null,
            'game_master_id' => $gm->id,
            'starts_at' => $data['starts_at'] ?? now(),
            'stop_ratio' => $data['stop_ratio'] ?? null,
            'draw_ratio' => $data['draw_ratio'] ?? 7,
            'planned_fights' => $data['planned_fights'] ?? null,
            'bomba_amount' => $data['bomba_amount'] ?? 0,
            'bomba_breakdown' => $data['bomba_breakdown'] ?? 3,
            'bomba_interval' => $data['bomba_interval'] ?? 30,
            'payout_display' => $data['payout_display'] ?? Event::DISPLAY_DECIMALS,
            'tv_total_bets' => $data['tv_total_bets'] ?? 100,
            'status' => Event::STATUS_DRAFT,
        ]);

        $this->audit->log('event.created', $event, $request->user(), [], [
            'name' => $event->name,
            'code' => $event->code,
            'type' => $event->type,
            'entries' => $event->entries,
            'stop_ratio' => $event->stop_ratio,
        ]);

        return back()->with('status', $event->typeLabel().' event '.$event->name.' created.');
    }

    /**
     * Codes are generated, not typed: people were reusing or guessing them.
     */
    private function nextCode(string $type): string
    {
        $prefix = $type === Event::TYPE_DERBY ? 'DRB' : 'REG';

        do {
            $code = $prefix.'-'.now()->format('ymd').'-'.strtoupper(Str::random(4));
        } while (Event::where('code', $code)->exists());

        return $code;
    }

    public function edit(Request $request, Event $event)
    {
        abort_unless($event->game_master_id === $request->user()->gameMasterProfile?->id, 403);

        // What the TV box on this form is describing: the card's pool so far.
        $tvPool = (int) Bet::whereHas('fight', fn ($q) => $q->where('event_id', $event->id))
            ->whereIn('status', [Bet::STATUS_ACTIVE, Bet::STATUS_WON, Bet::STATUS_LOST])
            ->sum('amount_credits');

        return view('gm.event-edit', [
            'event' => $event,
            'tvPool' => $tvPool,
            'tvShare' => (int) ($event->tv_total_bets ?? 100),
            'tvStake' => (int) round($tvPool * ((int) ($event->tv_total_bets ?? 100)) / 100),
        ]);
    }

    public function update(Request $request, Event $event)
    {
        abort_unless($event->game_master_id === $request->user()->gameMasterProfile?->id, 403);

        if (in_array($event->status, [Event::STATUS_CLOSED, Event::STATUS_CANCELLED], true)) {
            return back()->with('error', 'A closed or cancelled event is locked. Create a new event instead.');
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'starts_at' => ['nullable', 'date'],
            'status' => ['required', 'in:draft,open'],
            'stop_ratio' => ['nullable', 'numeric', 'min:1.01', 'max:20'],
            'draw_ratio' => ['nullable', 'numeric', 'min:1', 'max:100'],
            'planned_fights' => ['nullable', 'integer', 'min:1', 'max:500'],
            'bomba_amount' => ['nullable', 'integer', 'min:0', 'max:100000000'],
            'bomba_breakdown' => ['nullable', 'integer', 'min:0', 'max:100'],
            'bomba_interval' => ['nullable', 'integer', 'min:0', 'max:3600'],
            'payout_display' => ['required', 'in:decimals,whole'],
            'tv_total_bets' => ['required', 'in:100,70,50'],
            'entries' => ['nullable', 'string', 'max:2000'],
        ]);

        $entries = collect(preg_split('/\r\n|\r|\n/', (string) ($data['entries'] ?? '')))
            ->map(fn ($entry) => trim((string) $entry))
            ->filter()
            ->values()
            ->all();

        if ($event->isDerby() && count($entries) < 2) {
            return back()->withErrors(['entries' => 'A derby needs at least two entry names, one per line.'])->withInput();
        }

        $before = $event->only([
            'name', 'starts_at', 'status', 'stop_ratio', 'draw_ratio', 'planned_fights',
            'bomba_amount', 'bomba_breakdown', 'bomba_interval', 'payout_display', 'tv_total_bets',
        ]);

        $event->fill([
            'name' => $data['name'],
            'starts_at' => $data['starts_at'] ?? $event->starts_at,
            'status' => $data['status'],
            'stop_ratio' => $data['stop_ratio'] ?? null,
            'draw_ratio' => $data['draw_ratio'] ?? 7,
            'planned_fights' => $data['planned_fights'] ?? null,
            'bomba_amount' => $data['bomba_amount'] ?? 0,
            'bomba_breakdown' => $data['bomba_breakdown'] ?? 3,
            'bomba_interval' => $data['bomba_interval'] ?? 30,
            'payout_display' => $data['payout_display'],
            'tv_total_bets' => (int) $data['tv_total_bets'],
            'entries' => $event->isDerby() ? $entries : $event->entries,
        ]);

        $event->save();

        // Fights copy the card's stop ratio when they are created. If the card
        // changes later, open and scheduled fights must follow it or the old
        // cap keeps paying less than the odds on screen.
        if ($event->wasChanged('stop_ratio')) {
            $event->fights()
                ->whereNotIn('status', [Fight::STATUS_SETTLED, Fight::STATUS_VOID, Fight::STATUS_CANCELLED])
                ->update(['stop_ratio' => $event->stop_ratio]);
        }

        $this->audit->log('event.updated', $event, $request->user(), $before, $event->only(array_keys($before)));

        return redirect()->route('gm.events.edit', $event)->with('status', 'Event '.$event->name.' updated.');
    }

    public function close(Request $request, Event $event)
    {
        abort_unless($event->game_master_id === $request->user()->gameMasterProfile?->id, 403);

        if (in_array($event->status, [Event::STATUS_CLOSED, Event::STATUS_CANCELLED], true)) {
            return back()->with('error', 'This event is already closed.');
        }

        $before = ['status' => $event->status];
        $event->status = Event::STATUS_CLOSED;
        $event->ends_at = now();
        $event->save();

        $this->audit->log('event.closed', $event, $request->user(), $before, ['status' => $event->status]);

        return back()->with('status', 'Event '.$event->name.' closed.');
    }

    public function updateStatus(Request $request, Event $event)
    {
        abort_unless($event->game_master_id === $request->user()->gameMasterProfile?->id, 403);

        $data = $request->validate([
            'status' => ['required', 'in:draft,open,closed,cancelled'],
        ]);

        if (in_array($event->status, [Event::STATUS_CLOSED, Event::STATUS_CANCELLED], true)) {
            return back()->with('error', 'A closed or cancelled event cannot change status again. Create a new event instead.');
        }

        $before = ['status' => $event->status];
        $event->status = $data['status'];
        $event->save();

        $this->audit->log('event.status_changed', $event, $request->user(), $before, ['status' => $event->status]);

        return back()->with('status', 'Event '.$event->name.' is now '.$event->status.'.');
    }
}
