@extends('layouts.app')

@section('title', 'Events')
@section('heading', 'Events')
@section('subheading', 'Every event has a title. A regular event is a fight day; a derby also lists its entries. Event codes are generated for you. The bet stopper ratio, bomba settings and TV share you set here apply to every fight in the event.')

@section('content')
    <div class="card" style="margin-bottom:16px">
        <div class="card-head"><h2>Create event</h2></div>

        <form method="POST" action="{{ route('gm.events.store') }}">
            @csrf
            <div class="field">
                <span>Event type</span>
                <div class="radio-row">
                    <label class="radio">
                        <input type="radio" name="type" value="regular" data-event-type @checked(old('type', 'regular') === 'regular')>
                        <span>Regular — fights only</span>
                    </label>
                    <label class="radio">
                        <input type="radio" name="type" value="derby" data-event-type @checked(old('type') === 'derby')>
                        <span>Derby — fights and entries</span>
                    </label>
                </div>
            </div>

            <div class="form-grid">
                <label class="field">
                    <span>Event title</span>
                    <input class="input" name="name" placeholder="Friday Fight Night" value="{{ old('name') }}" required>
                </label>
                <label class="field"><span>Event date</span><input class="input" type="date" name="starts_at" value="{{ old('starts_at', now()->format('Y-m-d')) }}"></label>
                <label class="field">
                    <span>Bet stopper payout ratio</span>
                    <input class="input" type="number" step="0.001" min="1.01" max="20" name="stop_ratio"
                           value="{{ old('stop_ratio') }}" placeholder="e.g. 1.400">
                </label>
                <label class="field"><span>Venue</span><input class="input" name="venue" value="{{ old('venue') }}"></label>
                <label class="field"><span>Number of fights</span><input class="input" type="number" min="1" max="500" name="planned_fights" value="{{ old('planned_fights') }}" placeholder="e.g. 500"></label>
                <label class="field"><span>Bomba amount</span><input class="input" type="number" min="0" name="bomba_amount" value="{{ old('bomba_amount', 0) }}"></label>
                <label class="field"><span>Bomba break down</span><input class="input" type="number" min="0" max="100" name="bomba_breakdown" value="{{ old('bomba_breakdown', 3) }}"></label>
                <label class="field"><span>Bomba interval</span><input class="input" type="number" min="0" max="3600" name="bomba_interval" value="{{ old('bomba_interval', 30) }}"></label>
                <label class="field">
                    <span>Draw payout ratio</span>
                    <input class="input" type="number" step="0.001" min="1" max="100" name="draw_ratio"
                           value="{{ old('draw_ratio', 7) }}" placeholder="e.g. 7">
                </label>
            </div>

            <div class="form-grid">
                <div class="field">
                    <span>Payout ratio display</span>
                    <div class="radio-row">
                        <label class="radio">
                            <input type="radio" name="payout_display" value="decimals" @checked(old('payout_display', 'decimals') === 'decimals')>
                            <span>With decimals</span>
                        </label>
                        <label class="radio">
                            <input type="radio" name="payout_display" value="whole" @checked(old('payout_display') === 'whole')>
                            <span>Whole numbers</span>
                        </label>
                    </div>
                </div>

            </div>

            <div class="field" style="margin-top:16px">
                <span>TV total bets</span>
                <div class="tv-picker">
                    <div class="tv-options">
                        @foreach ([100 => 'Full pool', 70 => 'Most of the pool', 50 => 'Half the pool'] as $percent => $note)
                            <label class="tv-option">
                                <input type="radio" name="tv_total_bets" value="{{ $percent }}" @checked((int) old('tv_total_bets', 100) === $percent)>
                                <span class="tv-pct">{{ $percent }}<small>%</small></span>
                                <span class="tv-note">{{ $note }}</span>
                            </label>
                        @endforeach
                    </div>

                    <div class="tv-box" data-tv-preview data-pool="0" title="What the arena board will show">
                        <div class="tv-screen">
                            <span class="tv-label">TV total bets</span>
                            <strong data-tv-percent>{{ (int) old('tv_total_bets', 100) }}%</strong>
                            <small><span data-tv-stake>0</span> cr shown</small>
                        </div>
                    </div>
                </div>
            </div>

            <label class="field" data-for-type="derby">
                <span>Entry names — one per line, at least two</span>
                <textarea class="input" name="entries" rows="4" data-required-for-derby
                          placeholder="Sunrise Ace&#10;Night Fury&#10;Golden Spur">{{ old('entries') }}</textarea>
            </label>

            <button class="btn btn-primary">Create event</button>
        </form>

        <script src="{{ asset_v('js/tvbox.js') }}" defer></script>
    </div>

    <div class="card">
        <div class="card-head"><h2>My events</h2></div>

        <div class="table-wrap">
            <table class="table">
                <thead><tr><th>Code</th><th>Event</th><th>Type</th><th>Venue</th><th class="num">Fights</th><th>Status</th><th>Starts</th><th>Change status</th></tr></thead>
                <tbody>
                @forelse ($events as $event)
                    <tr>
                        <td class="mono">{{ $event->code }}</td>
                        <td>
                            <strong>{{ $event->name }}</strong>
                            @if ($event->isDerby() && $event->entryNames())
                                <ul class="entry-list">
                                    @foreach ($event->entryNames() as $entry)
                                        <li>{{ $entry }}</li>
                                    @endforeach
                                </ul>
                            @endif
                        </td>
                        <td><span class="badge {{ $event->isDerby() ? 'info' : 'muted' }}">{{ $event->typeLabel() }}</span></td>
                        <td class="muted">{{ $event->venue ?? '—' }}</td>
                        <td class="num">{{ $event->fights_count }}</td>
                        <td><span class="badge {{ $event->status === 'open' ? 'ok' : ($event->status === 'cancelled' ? 'err' : 'muted') }}">{{ $event->status }}</span></td>
                        <td class="muted small">{{ $event->starts_at?->format('d M Y H:i') ?? '—' }}</td>
                        <td>
                            @if (in_array($event->status, ['closed', 'cancelled'], true))
                                <a class="small" href="{{ route('gm.events.edit', $event) }}">view</a>
                            @else
                                <form method="POST" action="{{ route('gm.events.status', $event) }}" class="inline-form">
                                    @csrf
                                    @method('PATCH')
                                    <select class="input" name="status" style="width:120px">
                                        @foreach (['draft', 'open', 'closed', 'cancelled'] as $status)
                                            <option value="{{ $status }}" @selected($event->status === $status)>{{ $status }}</option>
                                        @endforeach
                                    </select>
                                    <button class="btn btn-sm">Apply</button>
                                    <a class="btn btn-sm btn-ghost" href="{{ route('gm.events.edit', $event) }}">Edit</a>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="muted">No events yet.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <script src="{{ asset_v('js/events.js') }}" defer></script>
@endsection
