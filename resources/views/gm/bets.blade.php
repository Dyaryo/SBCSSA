@extends('layouts.app')

@section('title', 'Bet history')
@section('heading', 'Bet history')
@section('subheading', 'Every simulated bet from your tellers, with the ledger-backed void action.')

@section('content')
    <div class="summary-strip">
        <div class="tile"><span>Meron bets</span><strong>{{ number_format($summary['meron']) }}</strong></div>
        <div class="tile"><span>Wala bets</span><strong>{{ number_format($summary['wala']) }}</strong></div>
        <div class="tile"><span>Draw bets</span><strong>{{ number_format($summary['draw']) }}</strong></div>
        <div class="tile"><span>Total bets</span><strong>{{ number_format($summary['total']) }}</strong></div>
    </div>

    <div class="card">
        <div class="card-head">
            <h2>Bets</h2>

            <form method="GET" class="row" style="gap:6px">
                <select class="input" name="fight_id" style="width:230px">
                    <option value="">All fights</option>
                    @foreach ($fights as $fight)
                        <option value="{{ $fight->id }}" @selected(request('fight_id') == $fight->id)>#{{ $fight->id }} · {{ $fight->label() }}</option>
                    @endforeach
                </select>

                <select class="input" name="status" style="width:140px">
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
                <thead>
                    <tr>
                        <th>Timestamp</th>
                        <th>Cashier</th>
                        <th>Account no.</th>
                        <th>Fight no.</th>
                        <th class="num">Bet amount</th>
                        <th class="num">Payout ratio</th>
                        <th>Winning side</th>
                        <th>Status</th>
                        <th>Result</th>
                        <th class="num">Payout</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                @forelse ($bets as $bet)
                    @php($ratio = $bet->amount_credits > 0 ? $bet->potential_payout / $bet->amount_credits : 0)
                    <tr>
                        <td class="muted small">{{ $bet->placed_at?->format('d M Y H:i:s') }}</td>
                        <td>{{ $bet->teller?->displayName() ?? '—' }}</td>
                        <td class="mono small">{{ $bet->teller?->teller_code ?? '—' }}</td>
                        <td><strong>#{{ $bet->fight?->fight_no }}</strong>
                            <div class="muted small">{{ $bet->fight?->event?->name }}</div>
                        </td>
                        <td class="num">{{ number_format($bet->amount_credits) }}</td>
                        <td class="num mono">{{ number_format($ratio, 3) }}×</td>
                        <td>
                            @if ($bet->fight?->result)
                                <span class="badge {{ $bet->fight->result === 'meron' ? 'red' : ($bet->fight->result === 'wala' ? 'blue' : 'muted') }}">
                                    {{ $bet->fight->resultLabel() }}
                                </span>
                            @else
                                <span class="muted">—</span>
                            @endif
                        </td>
                        <td><span class="badge {{ $bet->status === 'won' ? 'ok' : ($bet->status === 'lost' ? 'muted' : 'warn') }}">{{ $bet->status }}</span></td>
                        <td class="muted small">{{ $bet->fight?->status ?? '—' }}</td>
                        <td class="num">{{ number_format($bet->potential_payout) }}</td>
                        <td>
                            @if ($bet->status === 'active' && ! $bet->fight?->isTerminal())
                                <form method="POST" action="{{ route('gm.bets.void', $bet) }}" class="inline-form" data-confirm="Void bet #{{ $bet->id }} and refund the stake?">
                                    @csrf
                                    <input class="input" name="reason" placeholder="reason" style="width:110px" required>
                                    <button class="btn btn-sm btn-red">Void</button>
                                </form>
                            @else
                                —
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="11" class="muted">No bets match the filter.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>

        <div class="pagination">{{ $bets->links() }}</div>
    </div>
@endsection
