@extends('layouts.app')

@section('title', 'Bet history — Fight #'.$fight->fight_no)
@section('heading', 'Fight #'.$fight->fight_no.' bet history')
@section('subheading', $fight->event?->name.' · every bet taken on this fight, including voided and paid slips')

@section('content')
    <div class="row" style="margin-bottom:12px">
        <a class="btn btn-sm btn-ghost" href="{{ route('gm.fights.show', $fight) }}">← Back to the console</a>
        <a class="btn btn-sm btn-ghost" href="{{ route('gm.fights') }}">All fights</a>
    </div>

    <div class="stats" style="margin-bottom:14px">
        <div class="stat"><div class="k">Bets</div><div class="v">{{ number_format($totals['count']) }}</div></div>
        <div class="stat"><div class="k">On meron</div><div class="v red">{{ number_format($totals['meron']) }}</div></div>
        <div class="stat"><div class="k">On wala</div><div class="v blue">{{ number_format($totals['wala']) }}</div></div>
        <div class="stat"><div class="k">Paid</div><div class="v gold">{{ number_format($totals['paid']) }}</div></div>
        <div class="stat"><div class="k">Refunded</div><div class="v">{{ number_format($totals['refunded']) }}</div></div>
    </div>

    <div class="card">
        <div class="card-head">
            <h2>Every slip on this fight</h2>
            <span class="muted small">
                odds {{ number_format($fight->odds_red, 3) }} / {{ number_format($fight->odds_blue, 3) }}
                @if ($fight->isStopRatioActive())
                    · stop {{ number_format($fight->stop_ratio, 3) }}×
                @endif
            </span>
        </div>

        <div class="table-wrap">
            <table class="table">
                <thead>
                <tr>
                    <th>#</th>
                    <th>Teller</th>
                    <th>Shift</th>
                    <th>Side</th>
                    <th class="num">Amount</th>
                    <th class="num">Odds</th>
                    <th class="num">Payout</th>
                    <th>Status</th>
                    <th>Placed</th>
                    <th>Note</th>
                </tr>
                </thead>
                <tbody>
                @forelse ($bets as $bet)
                    <tr class="{{ $bet->status === 'won' ? 'win-row' : '' }}">
                        <td class="mono">{{ $bet->id }}</td>
                        <td>
                            {{ $bet->teller?->teller_code }}
                            <div class="muted small">{{ $bet->teller?->displayName() }}</div>
                        </td>
                        <td class="mono small">#{{ $bet->shift_session_id ?? '—' }}</td>
                        <td>
                            <span class="badge {{ $bet->side === 'red' ? 'red' : 'blue' }}">
                                {{ strtoupper(\App\Models\Fight::sideLabel($bet->side)) }}
                            </span>
                        </td>
                        <td class="num">{{ number_format($bet->amount_credits) }}</td>
                        <td class="num mono">{{ number_format($bet->odds_at_placement, 3) }}</td>
                        <td class="num">{{ number_format($bet->potential_payout) }}</td>
                        <td><span class="badge {{ $bet->status === 'won' ? 'ok' : ($bet->status === 'lost' ? 'muted' : 'warn') }}">{{ $bet->status }}</span></td>
                        <td class="muted small">{{ $bet->placed_at?->format('d M H:i:s') }}</td>
                        <td class="muted small">
                            @if ($bet->paid_out_at)
                                Paid {{ $bet->paid_out_at->format('d M H:i') }}
                                @if ($bet->paidOutBy) by {{ $bet->paidOutBy->name }} @endif
                            @elseif ($bet->void_reason)
                                Voided: {{ $bet->void_reason }}
                            @elseif ($bet->status === 'won')
                                Waiting at the window
                            @else
                                —
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="10" class="muted">No slips were taken on this fight.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
