@extends('layouts.app')

@section('title', 'Shift #'.$shift->id)
@section('heading', 'Shift #'.$shift->id.' · '.($shift->teller?->teller_code ?? 'unbound'))
@section('subheading', ($shift->teller?->displayName() ?? '—').' · '.($shift->workstation?->code ?? 'unbound').' · GM '.($shift->teller?->gameMaster?->displayName() ?? '—'))

@section('content')
    @if ($totals['unsettled_count'] > 0)
        <div class="flash err" style="margin-bottom:14px">
            <strong>{{ $totals['unsettled_count'] }} unsettled bet(s): {{ number_format($totals['unsettled_stake']) }} credits staked, up to {{ number_format($totals['unsettled_payout']) }} credits payable.</strong>
            @if ($shift->status !== 'open')
                This shift is <strong>{{ $shift->status }}</strong> but the money is still owed — closing a shift does not
                settle or refund a bet. The Game Master must grade or void those fights.
            @else
                Grading those fights before the close settles the money properly. Closing now leaves the debt on the books.
            @endif
        </div>
    @elseif ($shift->status === 'open')
        <div class="flash ok" style="margin-bottom:14px">
            Nothing unsettled on this till — every bet has a result, so a close here only seals the totals.
        </div>
    @endif

    <div class="stats" style="margin-bottom:14px">
        <div class="stat"><div class="k">Bets</div><div class="v">{{ number_format($totals['count']) }}</div></div>
        <div class="stat"><div class="k">Stake taken</div><div class="v">{{ number_format($totals['stake']) }}</div></div>
        <div class="stat"><div class="k">Unsettled stake</div><div class="v gold">{{ number_format($totals['unsettled_stake']) }}</div></div>
        <div class="stat"><div class="k">Unsettled payout</div><div class="v gold">{{ number_format($totals['unsettled_payout']) }}</div></div>
        <div class="stat"><div class="k">Won, still at window</div><div class="v red">{{ number_format($totals['owed_at_window']) }}</div></div>
        <div class="stat"><div class="k">Paid out</div><div class="v">{{ number_format($totals['paid_out']) }}</div></div>
        <div class="stat"><div class="k">Refunded</div><div class="v blue">{{ number_format($totals['refunded']) }}</div></div>
    </div>

    @if ($pendingFights->isNotEmpty())
        <div class="card" style="margin-bottom:14px">
            <div class="card-head">
                <h2>Fights still to grade</h2>
                <span class="muted small">{{ $pendingFights->count() }}</span>
            </div>

            <div class="table-wrap">
                <table class="table">
                    <thead><tr><th>Fight</th><th>Event</th><th>Status</th><th class="num">Unsettled bets</th><th class="num">Stake</th></tr></thead>
                    <tbody>
                    @foreach ($pendingFights as $fight)
                        @php($fightBets = $unsettled->where('fight_id', $fight->id))
                        <tr data-pending-fight="{{ $fight->fight_no }}">
                            <td>
                                <strong>#{{ $fight->fight_no }}</strong>
                                <div class="muted small">Meron vs Wala</div>
                            </td>
                            <td class="muted small">{{ $fight->event?->name }}</td>
                            <td><span class="badge warn">{{ $fight->status }}</span></td>
                            <td class="num">{{ number_format($fightBets->count()) }}</td>
                            <td class="num">{{ number_format((int) $fightBets->sum('amount_credits')) }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>

            <p class="muted small" style="margin:10px 0 0">
                The Game Master has to grade these fights (meron, wala, draw or cancelled) before the money on them moves.
            </p>
        </div>
    @endif

    <div class="card" style="margin-bottom:14px">
        <div class="card-head">
            <h2>Unsettled bets</h2>
            <span class="muted small">{{ number_format($totals['unsettled_count']) }} waiting on a result</span>
        </div>

        <div class="table-wrap">
            <table class="table">
                <thead><tr><th>#</th><th>Fight</th><th>Side</th><th class="num">Stake</th><th class="num">Odds</th><th class="num">Pays if it wins</th><th>Placed</th></tr></thead>
                <tbody>
                @forelse ($unsettled as $bet)
                    <tr class="win-row" data-unsettled-bet="{{ $bet->id }}">
                        <td class="mono">{{ $bet->id }}</td>
                        <td>
                            {{ $bet->fight?->label() }}
                            <div class="muted small">{{ $bet->fight?->event?->name }} · {{ ucfirst((string) $bet->fight?->status) }}</div>
                        </td>
                        <td><span class="badge {{ $bet->side === 'red' ? 'red' : 'blue' }}">{{ \App\Models\Fight::sideLabel($bet->side) }}</span></td>
                        <td class="num">{{ number_format($bet->amount_credits) }}</td>
                        <td class="num mono">{{ number_format($bet->odds_at_placement, 3) }}</td>
                        <td class="num"><span class="payout-now">{{ number_format($bet->potential_payout) }}</span></td>
                        <td class="muted small">{{ $bet->placed_at?->format('d M H:i:s') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="muted">No unsettled bets on this shift.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="card">
        <div class="card-head"><h2>All bets on this shift</h2></div>

        <div class="table-wrap">
            <table class="table">
                <thead><tr><th>#</th><th>Fight</th><th>Side</th><th class="num">Stake</th><th class="num">Payout</th><th>Status</th><th>Paid out</th></tr></thead>
                <tbody>
                @forelse ($bets as $bet)
                    <tr>
                        <td class="mono">{{ $bet->id }}</td>
                        <td class="small">
                            {{ $bet->fight?->label() }}
                            <div class="muted small">{{ $bet->fight?->event?->name }}</div>
                        </td>
                        <td><span class="badge {{ $bet->side === 'red' ? 'red' : ($bet->side === 'blue' ? 'blue' : 'draw') }}">{{ \App\Models\Fight::sideLabel($bet->side) }}</span></td>
                        <td class="num">{{ number_format($bet->amount_credits) }}</td>
                        <td class="num">{{ number_format($bet->potential_payout) }}</td>
                        <td><span class="badge {{ $bet->status === 'won' ? 'ok' : ($bet->status === 'active' ? 'warn' : 'muted') }}">{{ $bet->status }}</span></td>
                        <td class="muted small">{{ $bet->paid_out_at?->format('d M H:i') ?? '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="muted">No bets on this shift.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>

        <div class="kv" style="margin-top:12px">
            <dt>Opened</dt><dd>{{ $shift->opened_at?->format('d M Y H:i') ?? '—' }}</dd>
            <dt>Closed</dt><dd>{{ $shift->closed_at?->format('d M Y H:i') ?? 'still open' }}</dd>
            <dt>Close reason</dt><dd>{{ $shift->closed_reason ?? '—' }}</dd>
            <dt>Closing note</dt><dd>{{ $shift->closing_note ?? '—' }}</dd>
        </div>

        <div class="row" style="margin-top:14px">
            <a class="btn btn-ghost" href="{{ route('supervisor.shifts') }}">← All shifts</a>
            <a class="btn btn-ghost" href="{{ route('supervisor.shifts.closeout', $shift) }}" target="_blank">Closeout slip</a>
        </div>
    </div>
@endsection
