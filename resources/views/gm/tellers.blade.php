@extends('layouts.app')

@section('title', 'Teller roster')
@section('heading', 'POS teller roster')
@section('subheading', 'Read-only view of the terminals on your events. Accounts, credits and shift interventions belong to the Supervisor.')

@section('content')
    <div class="card" style="margin-bottom:14px">
        <div class="card-head"><h2>This screen is read-only</h2></div>

        <p class="muted small" style="margin:0">
            POS logins, teller codes, opening credits, password resets, suspensions and shift force-closes are handled by
            the Supervisor — <strong>Supervisor → Accounts</strong> and <strong>Supervisor → POS shifts</strong>.
            Ask them for anything that changes an account. Your job on this page is to see who is on which terminal;
            you can reprint a closeout slip from the last shift column.
        </p>
    </div>

    <div class="card">
        <div class="card-head">
            <h2>My tellers</h2>
            <span class="muted small">No controls here by design</span>
        </div>

        <div class="table-wrap">
            <table class="table">
                <thead><tr><th>Code</th><th>Name</th><th>Workstation</th><th class="num">Bets</th><th class="num">Stake</th><th class="num">Balance</th><th>Status</th><th>Last shift</th></tr></thead>
                <tbody>
                @forelse ($tellers as $teller)
                    @php($lastShift = $teller->shifts->first())
                    <tr>
                        <td><strong>{{ $teller->teller_code }}</strong></td>
                        <td>{{ $teller->displayName() }}<div class="muted small mono">{{ $teller->user?->username }}</div></td>
                        <td class="muted small">{{ $teller->workstation?->code ?? 'unbound' }}</td>
                        <td class="num">{{ number_format($teller->bets_count) }}</td>
                        <td class="num gold">{{ number_format((int) $teller->stake_total) }}</td>
                        <td class="num">{{ number_format($teller->user?->creditBalance() ?? 0) }}</td>
                        <td><span class="badge {{ $teller->status === 'active' ? 'ok' : 'err' }}">{{ $teller->status }}</span></td>
                        <td class="small">
                            @if ($lastShift)
                                #{{ $lastShift->id }} · {{ $lastShift->status }}
                                <div><a href="{{ route('gm.shifts.closeout', $lastShift) }}" target="_blank">closeout slip</a></div>
                            @else
                                <span class="muted">no shift yet</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="muted">No tellers on your events yet. The Supervisor creates POS accounts.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
