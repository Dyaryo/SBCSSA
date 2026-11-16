@extends('layouts.app')

@section('title', 'Fight control')
@section('heading', 'Fight control')
@section('subheading')
    Operating as {{ $gm->displayName() }}. Your job here is the fights — schedule them, open and close the fight, grade the result, then start the next one.
@endsection

@section('content')
    <div data-live-refresh data-signature="{{ $liveSignature }}" data-feed-url="{{ route('gm.feed') }}"></div>
    <p class="muted small" style="margin:0 0 10px"><i class="live-dot"></i>Live — refreshes itself as tellers place bets</p>

    <div class="stats" style="margin-bottom:16px">
        <div class="stat"><div class="k">Events</div><div class="v">{{ $totals['events'] }}</div></div>
        <div class="stat"><div class="k">Fights</div><div class="v">{{ $totals['fights'] }}</div></div>
        <div class="stat"><div class="k">Betting open</div><div class="v green">{{ $totals['betting_open'] }}</div></div>
        <div class="stat"><div class="k">Live now</div><div class="v red">{{ $totals['live'] }}</div></div>
        <div class="stat"><div class="k">Awaiting grading</div><div class="v gold">{{ $totals['awaiting_grade'] }}</div></div>
        <div class="stat"><div class="k">Graded</div><div class="v">{{ $totals['settled'] }}</div></div>
    </div>

    <div class="card" style="margin-bottom:14px">
        <div class="card-head">
            <h2>Fights needing you</h2>
            <a class="small" href="{{ route('gm.fights') }}">All fights →</a>
        </div>

        <div class="table-wrap">
            <table class="table">
                <thead><tr><th>Fight</th><th>Event</th><th>Status</th><th class="num">Stake</th><th>Your next step</th></tr></thead>
                <tbody>
                @forelse ($activeFights as $fight)
                    @php($action = match ($fight->status) {
                        'scheduled' => 'Open fight',
                        'betting_open' => 'Close fight',
                        'betting_locked' => 'Mark live / grade',
                        default => 'Grade result',
                    })
                    <tr>
                        <td>
                            <strong>#{{ $fight->fight_no }}</strong>
                            <div class="muted small">Meron vs Wala</div>
                        </td>
                        <td class="muted small">{{ $fight->event?->name }}</td>
                        <td><span class="badge {{ $fight->status === 'live' ? 'err' : ($fight->status === 'betting_open' ? 'ok' : 'warn') }}">{{ $fight->status }}</span></td>
                        <td class="num">
                            {{ number_format($fight->stakedCredits()) }}
                            <div class="muted small">
                                meron {{ number_format($fight->stakedCredits(\App\Models\Fight::SIDE_RED)) }} ·
                                wala {{ number_format($fight->stakedCredits(\App\Models\Fight::SIDE_BLUE)) }}
                            </div>
                        </td>
                        <td><a class="btn btn-sm btn-primary" href="{{ route('gm.fights.show', $fight) }}">{{ $action }}</a></td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="muted">No fight is waiting on you. Schedule the next one from the Fights page.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="card" style="margin-bottom:14px">
        @include('partials.winners-road', [
            'cells' => $road['cells'],
            'cols' => $road['cols'],
            'count' => $road['count'],
        ])
    </div>

    <div class="grid two">
        <div class="card">
            <div class="card-head">
                <h2>Recent results</h2>
                <a class="small" href="{{ route('gm.fights', ['status' => 'settled']) }}">All graded →</a>
            </div>

            <div class="table-wrap">
                <table class="table">
                    <thead><tr><th>Fight</th><th>Result</th><th>Declared</th><th></th></tr></thead>
                    <tbody>
                    @forelse ($recentResults as $fight)
                        <tr>
                            <td>
                                <strong>#{{ $fight->fight_no }}</strong>
                                <div class="muted small">Meron vs Wala</div>
                            </td>
                            <td>
                                <span class="badge {{ $fight->result === 'meron' ? 'ok' : ($fight->result === 'wala' ? 'blue' : 'muted') }}">
                                    {{ $fight->resultLabel() }}
                                </span>
                            </td>
                            <td class="muted small">{{ $fight->declared_at?->format('d M H:i') ?? '—' }}</td>
                            <td><a class="small" href="{{ route('gm.fights.show', $fight) }}">Console</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="muted">No fight has been graded yet.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card">
            <div class="card-head">
                <h2>POS oversight</h2>
                <a class="small" href="{{ route('gm.monitor') }}">Monitor →</a>
            </div>

            <p class="muted small">
                Read-only view of the terminals. Tills, shifts and payouts belong to the POS tellers —
                they pay in on bets and pay out on winning slips. POS accounts and credits are created by the
                Supervisor, not by you.
            </p>

            <div class="kv">
                <dt>Tellers</dt><dd>{{ $totals['tellers'] }}</dd>
                <dt>Shifts open</dt><dd>{{ $totals['open_shifts'] }}</dd>
                <dt>Bets today</dt><dd>{{ number_format($todayBets) }}</dd>
                <dt>Stake (all time)</dt><dd>{{ number_format($totals['stake_total']) }} credits</dd>
            </div>

            <div class="row" style="margin-top:12px">
                <a class="btn btn-ghost btn-sm" href="{{ route('gm.tellers') }}">Teller roster</a>
                <a class="btn btn-ghost btn-sm" href="{{ route('gm.bets') }}">Bet history</a>
            </div>
        </div>
    </div>

    <script src="{{ asset_v('js/live-refresh.js') }}" defer></script>
@endsection
