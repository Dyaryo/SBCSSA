@extends('layouts.app')

@section('title', 'Reconciliation')
@section('heading', 'End-of-day reconciliation')
@section('subheading', 'Bets rebuilt from the bet table, cross-checked against the immutable wallet ledger')

@section('content')
    @php($t = $data['totals'])

    <div class="card" style="margin-bottom:14px">
        <form method="GET" class="inline-form" style="gap:8px;align-items:flex-end;flex-wrap:wrap">
            <label class="field" style="margin:0">
                <span>Business date</span>
                <input class="input" type="date" name="date" value="{{ $data['date'] }}" style="width:170px">
            </label>
            <button class="btn">Rebuild</button>
            <a class="btn btn-ghost" href="{{ route('supervisor.reconciliation.csv', ['date' => $data['date']]) }}">Export CSV</a>
            <a class="btn btn-ghost" href="{{ route('supervisor.reports.snapshots') }}">Sealed snapshots</a>
        </form>

        <p class="muted small" style="margin:10px 0 0">
            Each teller's <strong>stake</strong>, <strong>payout</strong> and <strong>refunds</strong> are derived from the bets
            themselves, then compared with the matching <code>bet_hold</code>, <code>payout</code> and <code>void_refund</code>
            ledger rows. Any difference is flagged — a clean day shows zero issues.
        </p>
    </div>

    <div class="stats" style="margin-bottom:14px">
        <div class="stat"><div class="k">Stake accepted</div><div class="v">{{ number_format($t['stake']) }}</div></div>
        <div class="stat"><div class="k">Payout credited</div><div class="v">{{ number_format($t['payout']) }}</div></div>
        <div class="stat"><div class="k">Net (payout − stake + refunds)</div><div class="v {{ $t['net'] < 0 ? 'green' : 'gold' }}">{{ number_format($t['net']) }}</div></div>
        <div class="stat"><div class="k">Issues found</div><div class="v {{ $t['issues'] === 0 ? 'green' : 'red' }}">{{ $t['issues'] }}</div></div>
    </div>

    <div class="row" style="gap:10px;margin-bottom:14px;flex-wrap:wrap">
        <span class="badge {{ $t['issues'] === 0 ? 'ok' : 'red' }}">
            {{ $t['issues'] === 0 ? 'Ledger and bets agree' : $t['issues'].' mismatch(es) — investigate before closing the day' }}
        </span>
        <span class="badge {{ $data['open_shifts'] === 0 ? 'muted' : 'warn' }}">{{ $data['open_shifts'] }} shift(s) still open</span>
        <span class="badge {{ $data['unsettled_fights'] === 0 ? 'muted' : 'warn' }}">{{ $data['unsettled_fights'] }} fight(s) unsettled</span>
        <span class="badge muted">{{ number_format($data['credits_in_circulation']) }} credits in circulation</span>
    </div>

    <div class="card">
        <div class="card-head"><h2>Per teller · {{ \Illuminate\Support\Carbon::parse($data['date'])->format('d M Y') }}</h2></div>

        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Teller</th>
                        <th class="num">Shifts</th>
                        <th class="num">Bets</th>
                        <th class="num">Stake</th>
                        <th class="num">Ledger holds</th>
                        <th class="num">Payout</th>
                        <th class="num">Ledger payout</th>
                        <th class="num">Refunded</th>
                        <th class="num">Net</th>
                        <th class="num">Balance</th>
                        <th>Check</th>
                    </tr>
                </thead>
                <tbody>
                @forelse ($data['rows'] as $row)
                    <tr class="{{ count($row['issues']) ? 'row-bad' : '' }}">
                        <td>
                            <strong>{{ $row['code'] }}</strong>
                            <div class="muted small">{{ $row['name'] }} · {{ $row['game_master'] }}</div>
                        </td>
                        <td class="num">
                            {{ $row['shifts'] }}
                            @if ($row['open_shifts'] > 0)<div class="badge warn">open</div>@endif
                            @if ($row['forced_shifts'] > 0)<div class="muted small">{{ $row['forced_shifts'] }} forced</div>@endif
                        </td>
                        <td class="num">
                            {{ number_format($row['bets']) }}
                            @if ($row['active_bets'] > 0)<div class="muted small">{{ $row['active_bets'] }} pending</div>@endif
                        </td>
                        <td class="num">{{ number_format($row['stake']) }}</td>
                        <td class="num">{{ number_format($row['ledger_holds']) }}</td>
                        <td class="num">{{ number_format($row['payout']) }}</td>
                        <td class="num">{{ number_format($row['ledger_payout']) }}</td>
                        <td class="num">{{ number_format($row['refunded']) }}</td>
                        <td class="num {{ $row['net'] < 0 ? 'green' : '' }}">{{ number_format($row['net']) }}</td>
                        <td class="num">{{ number_format($row['balance']) }}</td>
                        <td>
                            @if (count($row['issues']))
                                <span class="badge red">mismatch</span>
                                <div class="muted small">{{ implode(' · ', $row['issues']) }}</div>
                            @else
                                <span class="badge ok">agrees</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="11" class="muted">No teller accounts exist yet.</td></tr>
                @endforelse
                </tbody>
                <tfoot>
                    <tr>
                        <td><strong>Day total</strong></td>
                        <td class="num">{{ number_format($t['shifts']) }}</td>
                        <td class="num">{{ number_format($t['bets']) }}</td>
                        <td class="num">{{ number_format($t['stake']) }}</td>
                        <td class="num"></td>
                        <td class="num">{{ number_format($t['payout']) }}</td>
                        <td class="num"></td>
                        <td class="num">{{ number_format($t['refunded']) }}</td>
                        <td class="num">{{ number_format($t['net']) }}</td>
                        <td class="num">{{ number_format($data['credits_in_circulation']) }}</td>
                        <td><span class="badge {{ $t['issues'] === 0 ? 'ok' : 'red' }}">{{ $t['issues'] === 0 ? 'clean' : $t['issues'].' issues' }}</span></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>

    <div class="card" style="margin-top:14px">
        <div class="card-head"><h2>Close-of-day checklist</h2></div>
        <ul class="muted small" style="margin:0;padding-left:20px;line-height:1.9">
            <li><strong>Zero issues</strong> — the day's bets and ledger agree. Seal an event report for the record.</li>
            <li><strong>No open shifts</strong> — ask the Game Master to force-close any shift left open, with a reason.</li>
            <li><strong>No unsettled fights</strong> — settle or void every fight before the day is signed off.</li>
            <li><strong>Activate</strong> a zero-balance teller instead of leaving a negative — never edit the database directly.</li>
            <li>Run <code>php artisan system:verify</code> and take a database backup before closing the day.</li>
        </ul>
    </div>
@endsection
