@extends('layouts.app')

@section('title', 'Live floor')
@section('heading', 'Live floor')
@section('subheading', 'Cards running, windows taking money and the winners road — refreshes itself as the floor moves.')

@section('content')
    <div data-live-refresh data-signature="{{ $liveSignature }}" data-feed-url="{{ route('supervisor.live.feed') }}"></div>

    <div class="stats" style="margin-bottom:14px">
        <div class="stat"><div class="k">Open windows</div><div class="v green">{{ $totals['open_windows'] }}</div></div>
        <div class="stat"><div class="k">Live fights</div><div class="v gold">{{ $totals['live_fights'] }}</div></div>
        <div class="stat"><div class="k">Cards on the floor</div><div class="v">{{ $totals['cards'] }}</div></div>
        <div class="stat"><div class="k">Staked on open fights</div><div class="v">{{ number_format($totals['staked']) }}</div></div>
        <div class="stat"><div class="k">Locked sides</div><div class="v {{ $totals['locked_sides'] ? 'red' : '' }}">{{ $totals['locked_sides'] }}</div></div>
        <div class="stat"><div class="k">Shifts open</div><div class="v">{{ $totals['open_shifts'] }}</div></div>
    </div>

    <div class="card" style="margin-bottom:14px">
        <div class="card-head">
            <h2>Open betting windows</h2>
            <span class="muted small">{{ $windows->count() }} fight(s) on the floor</span>
        </div>

        <div class="table-wrap">
            <table class="table cards-table">
                <thead>
                    <tr>
                        <th>Fight</th><th>Card</th><th>Game Master</th><th>Status</th>
                        <th class="num">Meron</th><th class="num">Wala</th><th class="num">Draw</th>
                        <th class="num">Staked</th><th>Side locks</th><th>Opened</th>
                    </tr>
                </thead>
                <tbody>
                @forelse ($windows as $window)
                    @php($fight = $window->fight)
                    <tr>
                        <td>
                            <strong>#{{ $fight->fight_no }}</strong>
                            <div class="rate-breakdown">
                                <div class="rate-row">
                                    <span class="rate-side">MERON</span>
                                    <span class="rate-posted">posted {{ number_format($fight->odds_red, 3) }}×</span>
                                    <span class="rate-pays {{ $fight->isCappedFor(\App\Models\Fight::SIDE_RED) ? 'is-capped' : '' }}">PAYS {{ number_format($fight->effectiveOddsFor(\App\Models\Fight::SIDE_RED), 3) }}×{{ $fight->isCappedFor(\App\Models\Fight::SIDE_RED) ? ' · capped' : '' }}</span>
                                </div>
                                <div class="rate-row">
                                    <span class="rate-side">WALA</span>
                                    <span class="rate-posted">posted {{ number_format($fight->odds_blue, 3) }}×</span>
                                    <span class="rate-pays {{ $fight->isCappedFor(\App\Models\Fight::SIDE_BLUE) ? 'is-capped' : '' }}">PAYS {{ number_format($fight->effectiveOddsFor(\App\Models\Fight::SIDE_BLUE), 3) }}×{{ $fight->isCappedFor(\App\Models\Fight::SIDE_BLUE) ? ' · capped' : '' }}</span>
                                </div>
                            </div>
                            <div class="muted small">draw {{ number_format($fight->drawRatio(), 3) }}×</div>
                        </td>
                        <td>{{ $fight->event?->name }}</td>
                        <td class="muted small">{{ $fight->event?->gameMaster?->displayName() ?? '—' }}</td>
                        <td>
                            <span class="badge {{ $fight->status === 'live' ? 'warn' : 'ok' }}">{{ str_replace('_', ' ', $fight->status) }}</span>
                        </td>
                        <td class="num {{ $fight->isSideLocked('red') ? 'muted' : '' }}">{{ number_format($window->red) }}</td>
                        <td class="num {{ $fight->isSideLocked('blue') ? 'muted' : '' }}">{{ number_format($window->blue) }}</td>
                        <td class="num">{{ number_format($window->draw) }}</td>
                        <td class="num"><strong>{{ number_format($window->total) }}</strong></td>
                        <td>
                            @if ($fight->isSideLocked('red'))
                                <span class="badge warn">MERON LOCKED</span>
                            @endif
                            @if ($fight->isSideLocked('blue'))
                                <span class="badge warn">WALA LOCKED</span>
                            @endif
                            @if (! $fight->isSideLocked('red') && ! $fight->isSideLocked('blue'))
                                <span class="muted small">both open</span>
                            @endif
                        </td>
                        <td class="muted small">{{ $fight->betting_opened_at?->format('H:i') ?? '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="10" class="muted">No window is open right now. Waiting for a Game Master to open a fight.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="card" style="margin-bottom:14px">
        <div class="card-head">
            <h2>Current cards</h2>
            <a class="small" href="{{ route('supervisor.reports') }}">Reports →</a>
        </div>

        <div class="table-wrap">
            <table class="table cards-table">
                <thead>
                    <tr>
                        <th>Card</th><th>Game Master</th><th>Status</th><th class="num">Fights</th>
                        <th class="num">Bet stopper</th><th class="num">Draw</th><th class="num">TV</th>
                        <th>Bomba</th><th class="num">Planned</th>
                    </tr>
                </thead>
                <tbody>
                @forelse ($cards as $card)
                    <tr>
                        <td>
                            <strong>{{ $card->name }}</strong>
                            <div class="muted small mono">{{ $card->code }} · {{ $card->typeLabel() }} · {{ $card->venue ?? 'no venue' }}</div>
                        </td>
                        <td class="muted small">{{ $card->gameMaster?->displayName() ?? '—' }}</td>
                        <td><span class="badge {{ $card->status === 'open' ? 'ok' : 'muted' }}">{{ $card->status }}</span></td>
                        <td class="num">{{ $card->settled_fights_count }}<div class="muted small">of {{ $card->fights_count }} run</div></td>
                        <td class="num">{{ $card->hasStopRatio() ? number_format((float) $card->stop_ratio, 3).'×' : '—' }}</td>
                        <td class="num">{{ number_format($card->drawRatio(), 3) }}×</td>
                        <td class="num">{{ (int) ($card->tv_total_bets ?? 100) }}%</td>
                        <td class="muted small">{{ number_format((int) $card->bomba_amount) }} · break {{ (int) $card->bomba_breakdown }} · {{ (int) $card->bomba_interval }}s</td>
                        <td class="num">{{ $card->planned_fights ? number_format($card->planned_fights) : '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="muted">No card is running.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="card">
        @include('partials.winners-road', [
            'cells' => $road['cells'],
            'cols' => $road['cols'],
            'count' => $road['count'],
        ])
    </div>

    <script src="{{ asset_v('js/live-refresh.js') }}" defer></script>
@endsection
