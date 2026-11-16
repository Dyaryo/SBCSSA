@extends('layouts.app')

@section('title', 'Fights')
@section('heading', 'Fights')
@section('subheading', 'Schedule fights and set the fixed odds used for simulated payouts.')

@section('content')
    <div class="card">
        <div class="card-head"><h2>Schedule a fight</h2></div>

            @if ($events->isEmpty())
                <div class="empty">Create an event first, then come back to schedule fights.</div>
            @else
                <form method="POST" action="{{ route('gm.fights.store') }}">
                    @csrf
                    <div class="form-grid">
                        <label class="field">
                            <span>Event</span>
                            <select class="input" name="event_id" required>
                                @foreach ($events as $event)
                                    <option value="{{ $event->id }}" @selected(old('event_id') == $event->id)>{{ $event->name }}</option>
                                @endforeach
                            </select>
                        </label>
                        <label class="field"><span>Fight no.</span><input class="input" type="number" name="fight_no" min="1" value="{{ old('fight_no') }}" required></label>
                        <label class="field"><span>Meron odds</span><input class="input" type="number" step="0.001" min="1.01" max="20" name="odds_red" value="{{ old('odds_red', '1.850') }}" required></label>
                        <label class="field"><span>Wala odds</span><input class="input" type="number" step="0.001" min="1.01" max="20" name="odds_blue" value="{{ old('odds_blue', '2.050') }}" required></label>
                        <label class="field"><span>Scheduled at</span><input class="input" type="datetime-local" name="scheduled_at" value="{{ old('scheduled_at') }}"></label>
                    </div>
                    <p class="muted small" style="margin:0 0 10px">
                        Meron and Wala are the two sides. The stop bet ratio is set on the event and applies to every fight in it.
                    </p>
                    <button class="btn btn-primary">Schedule fight</button>
                </form>
            @endif
        </div>


    <div class="card" style="margin-top:14px">
        <div class="card-head">
            <h2>All fights</h2>
            <form method="GET" class="row" style="gap:6px">
                <select class="input" name="event_id" style="width:190px">
                    <option value="">All events</option>
                    @foreach ($events as $event)
                        <option value="{{ $event->id }}" @selected(request('event_id') == $event->id)>{{ $event->name }}</option>
                    @endforeach
                </select>
                <select class="input" name="status" style="width:150px">
                    <option value="">Any status</option>
                    @foreach (['scheduled', 'betting_open', 'betting_locked', 'live', 'settled', 'void', 'cancelled'] as $status)
                        <option value="{{ $status }}" @selected(request('status') === $status)>{{ $status }}</option>
                    @endforeach
                </select>
                <button class="btn btn-sm">Filter</button>
            </form>
        </div>

        <div class="table-wrap">
            <table class="table">
                <thead><tr><th>#</th><th>Event</th><th>Odds M / W</th><th class="num">Stake</th><th>Status</th><th>Winner</th><th>Actions</th></tr></thead>
                <tbody>
                @forelse ($fights as $fight)
                    <tr>
                        <td class="mono">{{ $fight->id }}</td>
                        <td class="muted small">
                            <strong>#{{ $fight->fight_no }}</strong>
                            <div>{{ $fight->event?->name }}</div>
                        </td>
                        <td class="mono small">
                            {{ number_format($fight->odds_red, 3) }} / {{ number_format($fight->odds_blue, 3) }}
                            @if ($fight->isStopRatioActive())
                                <div class="stop-note">stop {{ number_format($fight->stop_ratio, 3) }}×</div>
                            @endif
                        </td>
                        <td class="num">{{ number_format($fight->stakedCredits()) }}</td>
                        <td><span class="badge {{ $fight->status === 'settled' ? 'ok' : ($fight->status === 'void' ? 'err' : 'warn') }}">{{ $fight->status }}</span></td>
                        <td>
                            @if ($fight->winner)
                                <span class="badge {{ $fight->winner === 'red' ? 'red' : 'blue' }}">{{ strtoupper($fight->winner === 'red' ? 'meron' : 'wala') }}</span>
                            @else
                                —
                            @endif
                        </td>
                        <td>
                            <div class="actions">
                                <a class="btn btn-sm" href="{{ route('gm.fights.show', $fight) }}">Console</a>
                                <a class="btn btn-sm btn-ghost" href="{{ route('gm.fights.history', $fight) }}">History</a>

                                @if ($fight->status === 'betting_open')
                                    <form method="POST" action="{{ route('gm.fights.lock', $fight) }}"
                                          data-confirm="Close fight #{{ $fight->fight_no }}? No more bets will be accepted.">
                                        @csrf
                                        <button class="btn btn-sm btn-red">Close fight</button>
                                    </form>
                                @elseif (in_array($fight->status, ['scheduled', 'betting_locked'], true))
                                    <form method="POST" action="{{ route('gm.fights.open', $fight) }}">
                                        @csrf
                                        <button class="btn btn-sm btn-green">Open fight</button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="muted">No fights scheduled.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>

        <div class="pagination">{{ $fights->links() }}</div>
    </div>
@endsection
