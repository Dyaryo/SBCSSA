@extends('layouts.app')

@section('title', 'POS shifts')
@section('heading', 'POS shifts')
@section('subheading', 'Every open terminal till and the money it still has riding on unsettled bets.')

@section('content')
    @if ($owedTotal['bets'] > 0)
        <div class="flash err" style="margin-bottom:14px">
            <strong>{{ number_format($owedTotal['bets']) }} unsettled bet(s) across open tills — {{ number_format($owedTotal['stake']) }} credits staked, up to {{ number_format($owedTotal['payout']) }} credits payable.</strong>
            A forced close does not settle or refund them. Open the shift to see the bets before you close anything.
        </div>
    @endif

    <div class="card" style="margin-bottom:14px">
        <div class="card-head">
            <h2>Open shifts</h2>
            <span class="muted small">{{ $open->count() }} open</span>
        </div>

        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr><th>Shift</th><th>Teller</th><th>Workstation</th><th class="num">Bets</th><th class="num">Stake</th><th class="num">Unsettled</th><th>Opened</th><th>Force close</th></tr>
                </thead>
                <tbody>
                @forelse ($open as $shift)
                    @php($owed = $owedByShift[$shift->id] ?? null)
                    @php($pending = $pendingFights[$shift->id] ?? collect())
                    @php($closeWarning = $pending->isEmpty()
                        ? 'Force-close shift #'.$shift->id.'? Its totals are sealed into a report snapshot and the teller cannot reopen it.'
                        : 'Force-close shift #'.$shift->id.'? '.$pending->count().' fight(s) still need grading: '
                            .$pending->map(fn ($f) => '#'.$f->fight_no.' Meron vs Wala')->implode(', ')
                            .' — those bets stay active and the money stays owed.')
                    <tr>
                        <td class="mono"><a href="{{ route('supervisor.shifts.show', $shift) }}">#{{ $shift->id }}</a></td>
                        <td>
                            {{ $shift->teller?->teller_code }}
                            <div class="muted small">{{ $shift->teller?->displayName() }}</div>
                        </td>
                        <td class="muted small">{{ $shift->workstation?->code ?? 'unbound' }}</td>
                        <td class="num">{{ number_format($shift->bets_count) }}</td>
                        <td class="num">{{ number_format((int) $shift->stake_total) }}</td>
                        <td class="num">
                            @if ($shift->unsettled_count > 0)
                                <a class="gold" href="{{ route('supervisor.shifts.show', $shift) }}">
                                    {{ number_format($shift->unsettled_count) }} · view
                                </a>
                                <div class="muted small">{{ number_format((int) ($owed->stake ?? 0)) }} staked</div>
                            @else
                                <span class="muted">0</span>
                            @endif
                        </td>
                        <td class="muted small">{{ $shift->opened_at?->format('d M H:i') }}</td>
                        <td>
                            <form method="POST" action="{{ route('supervisor.shifts.close', $shift) }}" class="inline-form"
                                  data-confirm="{{ $closeWarning }}">
                                @csrf
                                <input class="input" name="reason" placeholder="reason" style="width:150px" required>
                                <button class="btn btn-sm btn-red">Close</button>
                            </form>

                            @if ($pending->isNotEmpty())
                                <div class="small" style="color:#b3261e;margin-top:4px">
                                    Needs grading:
                                    {{ $pending->map(fn ($f) => '#'.$f->fight_no)->implode(', ') }}
                                </div>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="muted">No shift is open. Tills are all closed out.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>

        <p class="muted small" style="margin:10px 0 0">
            A forced close does not refund or settle anything: the bets stay active and the fight still has to be graded.
            The close is written to the audit log with your account as the actor.
        </p>
    </div>

    <div class="card">
        <div class="card-head"><h2>Recently closed</h2></div>

        <div class="table-wrap">
            <table class="table">
                <thead><tr><th>Shift</th><th>Teller</th><th class="num">Bets</th><th class="num">Stake</th><th>Closed</th><th>How</th></tr></thead>
                <tbody>
                @forelse ($recent as $shift)
                    <tr>
                        <td class="mono"><a href="{{ route('supervisor.shifts.show', $shift) }}">#{{ $shift->id }}</a></td>
                        <td>{{ $shift->teller?->teller_code }}<div class="muted small">{{ $shift->teller?->displayName() }}</div></td>
                        <td class="num">{{ number_format((int) $shift->total_bets_count) }}</td>
                        <td class="num">{{ number_format((int) $shift->total_amount_credits) }}</td>
                        <td class="muted small">{{ $shift->closed_at?->format('d M H:i') ?? '—' }}</td>
                        <td>
                            <span class="badge {{ $shift->status === 'force_closed' ? 'err' : 'muted' }}">{{ $shift->status }}</span>
                            @if ($shift->closed_reason)
                                <div class="muted small">{{ $shift->closed_reason }}</div>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="muted">No closed shifts yet.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
