@extends('layouts.app')

@section('title', 'Supervisor overview')
@section('heading', 'System overview')
@section('subheading', 'Cross-event monitoring across every Game Master and POS terminal.')

@section('content')
    <div data-live-refresh data-signature="{{ $liveSignature }}" data-feed-url="{{ route('supervisor.feed') }}"></div>
    <p class="muted small" style="margin:0 0 10px"><i class="live-dot"></i>Live — refreshes itself as bets and transactions come in</p>

    <div class="stats" style="margin-bottom:16px">
        <div class="stat"><div class="k">Events</div><div class="v">{{ number_format($totals['events']) }}</div></div>
        <div class="stat"><div class="k">Fights</div><div class="v">{{ number_format($totals['fights']) }}</div></div>
        <div class="stat"><div class="k">Settled</div><div class="v green">{{ number_format($totals['fights_settled']) }}</div></div>
        <div class="stat"><div class="k">Game Masters</div><div class="v">{{ number_format($totals['game_masters']) }}</div></div>
        <div class="stat"><div class="k">POS accounts</div><div class="v">{{ number_format($totals['tellers']) }}</div></div>
        <div class="stat"><div class="k">Shifts open</div><div class="v blue">{{ number_format($totals['active_pos']) }}</div></div>
        <div class="stat"><div class="k">Simulated bets</div><div class="v">{{ number_format($totals['bets']) }}</div></div>
        <div class="stat"><div class="k">Credits in circulation</div><div class="v gold">{{ number_format($totals['credits_in_circulation']) }}</div></div>
    </div>

    @include('supervisor.partials.low-tills')

    <div class="card" style="margin-bottom:14px">
        <div class="card-head">
            <h2>Teller cancellations</h2>
            <span class="muted small">
                Today: {{ number_format($tellerCancellationsToday) }} cancelled ·
                {{ number_format($tellerCancellationCreditsToday) }} credits refunded
            </span>
        </div>

        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr><th>When</th><th>Ticket</th><th>Teller</th><th>Fight</th><th class="num">Refunded</th><th>Reason</th><th>Cancelled by</th></tr>
                </thead>
                <tbody>
                @forelse ($tellerCancellations as $cancelled)
                    <tr>
                        <td class="muted small">{{ $cancelled->settled_at?->format('d M H:i:s') ?? '—' }}</td>
                        <td class="mono">#{{ $cancelled->id }}</td>
                        <td>
                            <strong>{{ $cancelled->teller?->teller_code ?? '—' }}</strong>
                            <div class="muted small">{{ $cancelled->teller?->displayName() }}</div>
                        </td>
                        <td class="small">
                            {{ $cancelled->fight?->label() }}
                            <div class="muted small">{{ $cancelled->fight?->event?->name }}</div>
                        </td>
                        <td class="num">{{ number_format($cancelled->amount_credits) }}</td>
                        <td class="small">{{ $cancelled->void_reason ?: '—' }}</td>
                        <td class="small">{{ $cancelled->voidedBy?->name ?? '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="muted">No teller cancellations recorded.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="grid two">
        <div class="card">
            <div class="card-head">
                <h2>Teller activity</h2>
                <a class="small" href="{{ route('supervisor.reports') }}">Open reports →</a>
            </div>

            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr><th>Teller</th><th>Game Master</th><th class="num">Bets</th><th class="num">Stake (credits)</th></tr>
                    </thead>
                    <tbody>
                    @forelse ($tellerActivity as $teller)
                        <tr>
                            <td>
                                <strong>{{ $teller->teller_code }}</strong>
                                <div class="muted small">{{ $teller->displayName() }}</div>
                            </td>
                            <td class="muted">{{ $teller->gameMaster?->displayName() ?? '—' }}</td>
                            <td class="num">{{ number_format($teller->bets_count) }}</td>
                            <td class="num gold">{{ number_format((int) $teller->stake_total) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="muted">No teller activity yet.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card">
            <div class="card-head"><h2>Terminals currently signed in</h2></div>

            <div class="table-wrap">
                <table class="table">
                    <thead><tr><th>Teller</th><th>Workstation</th><th>Opened</th></tr></thead>
                    <tbody>
                    @forelse ($openShifts as $shift)
                        <tr>
                            <td><strong>{{ $shift->teller?->teller_code }}</strong><div class="muted small">{{ $shift->teller?->displayName() }}</div></td>
                            <td>{{ $shift->workstation?->code ?? 'unbound' }}</td>
                            <td class="muted small">{{ $shift->opened_at?->format('d M H:i') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="muted">No open shifts.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card" style="margin-top:14px">
        <div class="card-head">
            <h2>Cards in play</h2>
            <span class="muted small">Event-form settings, exactly as the pit and the TV board use them</span>
        </div>

        <div class="table-wrap">
            <table class="table cards-table">
                <thead>
                    <tr>
                        <th>Card</th><th>Game Master</th><th>Status</th><th class="num">Fights</th>
                        <th class="num">Bet stopper</th><th class="num">Draw</th><th class="num">TV</th>
                        <th>Bomba</th><th>Payout display</th><th class="num">Staked</th>
                    </tr>
                </thead>
                <tbody>
                @forelse ($cards as $card)
                    <tr>
                        <td>
                            <strong>{{ $card->name }}</strong>
                            <div class="muted small mono">{{ $card->code }} · {{ $card->typeLabel() }} · {{ $card->venue ?? 'no venue' }}</div>
                        </td>
                        <td class="muted">{{ $card->gameMaster?->displayName() ?? '—' }}</td>
                        <td><span class="badge {{ $card->status === 'open' ? 'ok' : ($card->status === 'cancelled' ? 'err' : 'muted') }}">{{ $card->status }}</span></td>
                        <td class="num">{{ $card->planned_fights ? number_format($card->planned_fights) : '—' }}<div class="muted small">{{ $card->fights->count() }} run</div></td>
                        <td class="num">{{ $card->hasStopRatio() ? number_format((float) $card->stop_ratio, 3).'×' : '—' }}</td>
                        <td class="num">{{ number_format($card->drawRatio(), 3) }}×</td>
                        <td class="num">{{ (int) ($card->tv_total_bets ?? 100) }}%</td>
                        <td class="muted small">{{ number_format((int) $card->bomba_amount) }} · break {{ (int) $card->bomba_breakdown }} · {{ (int) $card->bomba_interval }}s</td>
                        <td class="muted small">{{ $card->payout_display === 'whole' ? 'Whole numbers' : 'With decimals' }}</td>
                        <td class="num">{{ number_format((int) $card->stake_total) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="10" class="muted">No cards have been created yet.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="card" style="margin-top:14px">
        <div class="card-head"><h2>Latest simulated bets</h2></div>

        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr><th>#</th><th>Fight</th><th>Teller</th><th>Side</th><th class="num">Amount</th><th>Status</th><th>Placed</th></tr>
                </thead>
                <tbody>
                @forelse ($recentBets as $bet)
                    <tr>
                        <td class="mono">{{ $bet->id }}</td>
                        <td>
                            {{ $bet->fight?->label() }}
                            <div class="muted small">{{ $bet->fight?->event?->name }}</div>
                        </td>
                        <td>{{ $bet->teller?->teller_code }}</td>
                        <td><span class="badge {{ $bet->side === 'red' ? 'red' : ($bet->side === 'blue' ? 'blue' : 'draw') }}">{{ \App\Models\Fight::sideLabel($bet->side) }}</span></td>
                        <td class="num">{{ number_format($bet->amount_credits) }}</td>
                        <td><span class="badge muted">{{ $bet->status }}</span></td>
                        <td class="muted small">{{ $bet->placed_at?->format('d M H:i:s') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="muted">No bets recorded yet.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <script src="{{ asset_v('js/live-refresh.js') }}" defer></script>
@endsection
