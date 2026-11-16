@extends('layouts.app')

@section('title', 'POS monitor')
@section('heading', 'POS monitor')
@section('subheading', 'Live view of terminals, open shifts and the bets flowing through your tellers.')

@section('content')
    <div class="grid two">
        <div class="card">
            <div class="card-head"><h2>Bind this terminal</h2></div>

            <p class="muted small">
                Run this from the physical POS computer. It stores that browser's fingerprint against a
                workstation record so strict device binding can recognise it.
            </p>

            <form method="POST" action="{{ route('gm.monitor.bind') }}">
                @csrf
                <input type="hidden" name="device_uuid" data-device-uuid value="">

                <div class="form-grid">
                    <label class="field">
                        <span>Workstation</span>
                        <select class="input" name="code" required>
                            @foreach ($workstations as $workstation)
                                <option value="{{ $workstation->code }}">{{ $workstation->code }} — {{ $workstation->label }}</option>
                            @endforeach
                        </select>
                    </label>

                    <label class="field">
                        <span>Assign teller (optional)</span>
                        <select class="input" name="assigned_teller_id">
                            <option value="">—</option>
                            @foreach ($tellers as $teller)
                                <option value="{{ $teller->id }}">{{ $teller->teller_code }} — {{ $teller->displayName() }}</option>
                            @endforeach
                        </select>
                    </label>
                </div>

                <button class="btn btn-primary">Bind this terminal</button>
                <p class="muted small" style="margin-top:8px">Fingerprint: <span class="mono" data-device-label>…</span></p>
            </form>
        </div>

        <div class="card">
            <div class="card-head"><h2>Workstations</h2></div>

            <div class="table-wrap">
                <table class="table">
                    <thead><tr><th>Code</th><th>Teller</th><th>Bound</th><th>Approved</th><th>Last seen</th></tr></thead>
                    <tbody>
                    @foreach ($workstations as $workstation)
                        <tr>
                            <td><strong>{{ $workstation->code }}</strong><div class="muted small">{{ $workstation->label }}</div></td>
                            <td>{{ $workstation->assignedTeller?->teller_code ?? '—' }}</td>
                            <td><span class="badge {{ $workstation->isBound() ? 'ok' : 'muted' }}">{{ $workstation->isBound() ? 'bound' : 'no' }}</span></td>
                            <td><span class="badge {{ $workstation->isApproved() ? 'ok' : 'warn' }}">{{ $workstation->isApproved() ? 'yes' : 'pending' }}</span></td>
                            <td class="muted small">{{ $workstation->last_seen_at?->format('d M H:i') ?? '—' }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card" style="margin-top:14px">
        <div class="card-head"><h2>Open shifts</h2></div>

        <div class="table-wrap">
            <table class="table">
                <thead><tr><th>#</th><th>Teller</th><th>Workstation</th><th>Opened</th><th class="num">Bets</th><th class="num">Stake</th><th>Closeout</th></tr></thead>
                <tbody>
                @forelse ($openShifts as $shift)
                    <tr>
                        <td class="mono">{{ $shift->id }}</td>
                        <td><strong>{{ $shift->teller?->teller_code }}</strong><div class="muted small">{{ $shift->teller?->displayName() }}</div></td>
                        <td class="muted">{{ $shift->workstation?->code ?? 'unbound' }}</td>
                        <td class="muted small">{{ $shift->opened_at?->format('d M H:i') }}</td>
                        <td class="num">{{ $shift->bets->count() }}</td>
                        <td class="num">{{ number_format((int) $shift->bets->sum('amount_credits')) }}</td>
                        <td>
                            <a class="small" href="{{ route('gm.shifts.closeout', $shift) }}" target="_blank">slip</a>
                            <div class="muted small">Supervisor closes shifts</div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="muted">No open shifts.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="card" style="margin-top:14px">
        <div class="card-head"><h2>Latest terminal activity</h2></div>

        <div class="table-wrap">
            <table class="table">
                <thead><tr><th>#</th><th>Teller</th><th>Fight</th><th>Side</th><th class="num">Amount</th><th>Status</th><th>Time</th></tr></thead>
                <tbody>
                @forelse ($recentBets as $bet)
                    <tr>
                        <td class="mono">{{ $bet->id }}</td>
                        <td>{{ $bet->teller?->teller_code }}</td>
                        <td class="small">{{ $bet->fight?->label() }}</td>
                        <td><span class="badge {{ $bet->side === 'red' ? 'red' : 'blue' }}">{{ $bet->side }}</span></td>
                        <td class="num">{{ number_format($bet->amount_credits) }}</td>
                        <td><span class="badge muted">{{ $bet->status }}</span></td>
                        <td class="muted small">{{ $bet->placed_at?->format('H:i:s') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="muted">No activity yet.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
