@extends('layouts.app')

@section('title', 'Event form — '.$event->name)
@section('heading', 'Event form')
@section('subheading', $event->code.' · '.$event->typeLabel().' event · '.$event->fights()->count().' fight(s)')

@section('content')
    <div class="card" style="max-width:980px">
        <div class="spread" style="margin-bottom:16px">
            <span class="badge {{ $event->status === 'open' ? 'ok' : ($event->status === 'cancelled' ? 'err' : 'muted') }}">
                {{ $event->status }}
            </span>

            @if (! in_array($event->status, ['closed', 'cancelled'], true))
                <form method="POST" action="{{ route('gm.events.close', $event) }}"
                      data-confirm="Close this event? The card is locked afterwards and cannot be reopened.">
                    @csrf
                    <button class="btn btn-red">Close event</button>
                </form>
            @else
                <span class="muted small">This card is locked.</span>
            @endif
        </div>

        <form method="POST" action="{{ route('gm.events.update', $event) }}">
            @csrf
            @method('PATCH')

            <div class="form-grid">
                <label class="field">
                    <span>Date</span>
                    <input class="input" type="date" name="starts_at"
                           value="{{ old('starts_at', $event->starts_at?->format('Y-m-d')) }}">
                </label>

                <label class="field">
                    <span>Status</span>
                    <select class="input" name="status" required>
                        @foreach (['draft', 'open'] as $status)
                            <option value="{{ $status }}" @selected(old('status', $event->status) === $status)>{{ $status }}</option>
                        @endforeach
                    </select>
                </label>

                <label class="field">
                    <span>Number of fights</span>
                    <input class="input" type="number" min="1" max="500" name="planned_fights"
                           value="{{ old('planned_fights', $event->planned_fights) }}" placeholder="e.g. 500">
                </label>

                <label class="field">
                    <span>Title</span>
                    <input class="input" name="name" value="{{ old('name', $event->getRawOriginal('name') ?? $event->name) }}" required>
                </label>

                <label class="field">
                    <span>Venue</span>
                    <input class="input" name="venue" value="{{ old('venue', $event->venue) }}">
                </label>

                <label class="field">
                    <span>Bomba amount</span>
                    <input class="input" type="number" min="0" name="bomba_amount" value="{{ old('bomba_amount', $event->bomba_amount ?? 0) }}">
                </label>

                <label class="field">
                    <span>Bomba break down</span>
                    <input class="input" type="number" min="0" max="100" name="bomba_breakdown" value="{{ old('bomba_breakdown', $event->bomba_breakdown ?? 3) }}">
                </label>

                <label class="field">
                    <span>Bomba interval</span>
                    <input class="input" type="number" min="0" max="3600" name="bomba_interval" value="{{ old('bomba_interval', $event->bomba_interval ?? 30) }}">
                </label>

                <label class="field">
                    <span>Bet stopper payout ratio</span>
                    <input class="input" type="number" step="0.001" min="1.01" max="20" name="stop_ratio"
                           value="{{ old('stop_ratio', $event->stop_ratio ? number_format((float) $event->stop_ratio, 3, '.', '') : '') }}"
                           placeholder="e.g. 1.400">
                </label>

                <label class="field">
                    <span>Draw payout ratio</span>
                    <input class="input" type="number" step="0.001" min="1" max="100" name="draw_ratio"
                           value="{{ old('draw_ratio', $event->draw_ratio !== null ? number_format((float) $event->draw_ratio, 3, '.', '') : '7.000') }}"
                           placeholder="e.g. 7">
                </label>
            </div>

            <div class="form-grid">
                <div class="field">
                    <span>Payout ratio display</span>
                    <div class="radio-row">
                        <label class="radio">
                            <input type="radio" name="payout_display" value="decimals"
                                   @checked(old('payout_display', $event->payout_display) === 'decimals')>
                            <span>With decimals</span>
                        </label>
                        <label class="radio">
                            <input type="radio" name="payout_display" value="whole"
                                   @checked(old('payout_display', $event->payout_display) === 'whole')>
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
                                <input type="radio" name="tv_total_bets" value="{{ $percent }}"
                                       @checked((int) old('tv_total_bets', $tvShare) === $percent)>
                                <span class="tv-pct">{{ $percent }}<small>%</small></span>
                                <span class="tv-note">{{ $note }}</span>
                            </label>
                        @endforeach
                    </div>

                    <div class="tv-box" data-tv-preview data-pool="{{ $tvPool }}" title="What the arena board shows for this card">
                        <div class="tv-screen">
                            <span class="tv-label">TV total bets</span>
                            <strong data-tv-percent>{{ (int) old('tv_total_bets', $tvShare) }}%</strong>
                            <small><span data-tv-stake>{{ number_format($tvStake) }}</span> cr shown</small>
                        </div>
                    </div>
                </div>
            </div>

            @if ($event->isDerby())
                <label class="field">
                    <span>Entry names — one per line, at least two</span>
                    <textarea class="input" name="entries" rows="4">{{ old('entries', implode("\n", $event->entryNames())) }}</textarea>
                </label>
            @endif

            @if (in_array($event->status, ['closed', 'cancelled'], true))
                <p class="muted small">Closed and cancelled cards are read-only.</p>
            @else
                <button class="btn btn-green">Update</button>
            @endif
        </form>

        <script src="{{ asset_v('js/tvbox.js') }}" defer></script>
    </div>
@endsection
