@extends('layouts.app')

@section('title', 'Top-up history')
@section('heading', 'Top-up history')
@section('subheading', ($user->tellerProfile?->teller_code ?? $user->username).' · '.$user->name.' · every supervisor credit grant, newest first')

@section('content')
    <div class="stats" style="margin-bottom:14px">
        <div class="stat">
            <div class="k">Total granted</div>
            <div class="v gold">{{ number_format($total) }}</div>
        </div>
        <div class="stat">
            <div class="k">Grants issued</div>
            <div class="v">{{ number_format($count) }}</div>
        </div>
        <div class="stat">
            <div class="k">Current balance</div>
            <div class="v">{{ number_format($user->creditBalance()) }}</div>
        </div>
        <div class="stat">
            <div class="k">Last top-up</div>
            <div class="v">{{ $latest?->created_at?->format('d M H:i') ?? '—' }}</div>
        </div>
    </div>

    <div class="card">
        <div class="card-head">
            <div>
                <h2>Credit grants</h2>
                <p class="muted small" style="margin:4px 0 0">
                    Amounts are virtual credits. Each grant includes the reason recorded at the time and the
                    supervisor who issued it.
                </p>
            </div>

            <a class="btn btn-sm btn-ghost" href="{{ route('supervisor.accounts') }}">Back to accounts</a>
        </div>

        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>When</th>
                        <th class="num">Amount</th>
                        <th class="num">Balance after</th>
                        <th>Reason</th>
                        <th>Issued by</th>
                        <th>Reference</th>
                    </tr>
                </thead>
                <tbody>
                @forelse ($topUps as $transaction)
                    <tr>
                        <td class="muted small">{{ $transaction->created_at?->format('d M Y H:i:s') }}</td>
                        <td class="num"><strong class="payout-now">+{{ number_format($transaction->amount_credits) }}</strong></td>
                        <td class="num">{{ number_format($transaction->balance_after) }}</td>
                        <td>{{ $transaction->reason ?: '—' }}</td>
                        <td class="muted small">{{ $transaction->performedBy?->name ?? 'system' }}</td>
                        <td class="mono small">{{ $transaction->reference_no ?? '—' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="muted">No top-ups have been issued to this teller yet.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>

        <div class="pagination">{{ $topUps->links() }}</div>

        <p class="muted small" style="margin:10px 0 0">
            The maximum single top-up is {{ number_format(config('pos.max_top_up')) }} credits.
            Voided and losing bets do not appear here; this report is only for credit grants.
        </p>
    </div>
@endsection
