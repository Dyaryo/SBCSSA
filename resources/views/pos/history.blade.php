@extends('layouts.app')

@section('title', 'My history')
@section('heading', 'My history')
@section('subheading', $teller->teller_code.' · only your own transactions are visible here')

@section('content')
    <div class="card" style="margin-bottom:14px">
        <div class="card-head">
            <h2>My bets</h2>

            <form method="GET" class="inline-form">
                <select class="input" name="status" style="width:150px">
                    <option value="">Any status</option>
                    @foreach (['active', 'won', 'lost', 'void', 'refunded'] as $status)
                        <option value="{{ $status }}" @selected(request('status') === $status)>{{ $status }}</option>
                    @endforeach
                </select>
                <button class="btn btn-sm">Filter</button>
            </form>
        </div>

        <div class="table-wrap">
            <table class="table">
                <thead><tr><th>#</th><th>Fight</th><th>Side</th><th class="num">Amount</th><th>Status</th><th>Placed</th><th></th></tr></thead>
                <tbody>
                @forelse ($bets as $bet)
                    <tr>
                        <td class="mono">{{ $bet->id }}</td>
                        <td class="small">{{ $bet->fight?->label() }}</td>
                        <td><span class="badge {{ $bet->side === 'red' ? 'red' : 'blue' }}">{{ $bet->side }}</span></td>
                        <td class="num">{{ number_format($bet->amount_credits) }}</td>
                        <td>
                            <span class="badge {{ $bet->status === 'won' ? 'ok' : ($bet->status === 'lost' ? 'muted' : 'warn') }}">{{ $bet->status }}</span>
                            @if ($bet->void_reason)
                                <div class="muted small">{{ $bet->void_reason }}</div>
                            @endif
                        </td>
                        <td class="muted small">{{ $bet->placed_at?->format('d M H:i:s') }}</td>
                        <td class="small">
                            @if ($bet->status === \App\Models\Bet::STATUS_VOID)
                                <a class="btn btn-sm btn-ghost"
                                   href="{{ route('pos.bets.cancellation', ['bet' => $bet, 'auto' => 1]) }}"
                                   target="_blank" rel="noopener">Reprint cancellation</a>
                            @else
                                <a href="{{ route('pos.bets.receipt', $bet) }}" target="_blank">slip</a>
                                @if ($bet->status === \App\Models\Bet::STATUS_ACTIVE && $bet->fight?->status === \App\Models\Fight::STATUS_BETTING_OPEN)
                                    · <a href="{{ route('pos.bets.cancel.form', $bet) }}">cancel</a>
                                @endif
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="muted">No bets recorded.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>

        <div class="pagination">{{ $bets->links() }}</div>
    </div>

    <div class="card">
        <div class="card-head"><h2>My recent shifts</h2></div>

        <div class="table-wrap">
            <table class="table">
                <thead><tr><th>#</th><th>Opened</th><th>Closed</th><th class="num">Bets</th><th class="num">Stake</th><th>Status</th><th>Note</th><th></th></tr></thead>
                <tbody>
                @forelse ($shifts as $shift)
                    <tr>
                        <td class="mono">{{ $shift->id }}</td>
                        <td class="muted small">{{ $shift->opened_at?->format('d M H:i') }}</td>
                        <td class="muted small">{{ $shift->closed_at?->format('d M H:i') ?? '—' }}</td>
                        <td class="num">{{ $shift->total_bets_count }}</td>
                        <td class="num">{{ number_format($shift->total_amount_credits) }}</td>
                        <td><span class="badge {{ $shift->status === 'open' ? 'warn' : 'muted' }}">{{ $shift->status }}</span></td>
                        <td class="muted small">{{ $shift->closing_note ?? $shift->closed_reason ?? $shift->opening_note ?? '—' }}</td>
                        <td class="small"><a href="{{ route('pos.shifts.closeout', $shift) }}" target="_blank">slip</a></td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="muted">No shifts yet.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
