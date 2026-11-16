@extends('layouts.app')

@section('title', 'Overall summary report')
@section('heading', 'Overall summary report')
@section('subheading', 'Every event, fight and bet in your card, totalled, with the commission split for the card. All figures are virtual credits.')

@section('content')
    <div class="card" style="margin-bottom:14px">
        <div class="card-head">
            <h2>Overall summary report</h2>
            <span class="muted small">Cash and commissions for the card</span>
        </div>

        <form method="GET" class="row" style="gap:10px;margin-bottom:12px">
            <label class="field" style="margin:0;min-width:250px">
                <span>Event</span>
                <select class="input" name="event_id">
                    <option value="">All events</option>
                    @foreach ($eventOptions as $option)
                        <option value="{{ $option->id }}" @selected(request('event_id') == $option->id)>{{ $option->name }}</option>
                    @endforeach
                </select>
            </label>
            <label class="field" style="margin:0;min-width:170px">
                <span>Cash on hand</span>
                <input class="input" type="number" name="cash_on_hand" min="0" value="{{ $cashCounted }}">
            </label>
            <label class="field" style="margin:0;min-width:170px">
                <span>Overall expenses</span>
                <input class="input" type="number" name="expenses" min="0" value="{{ $expenses }}">
            </label>
            <button class="btn btn-sm" style="align-self:flex-end">Recalculate</button>
        </form>

        <div class="report-sheet">
            @foreach ($sheet as [$label, $value, $note])
                <div class="report-row">
                    <div class="k">{{ $label }}</div>
                    <div class="v">{{ $value }}<span class="muted small"> · {{ $note }}</span></div>
                </div>
            @endforeach
        </div>
    </div>

    <div class="stats" style="margin-bottom:14px">
        <div class="stat"><div class="k">Events</div><div class="v">{{ $summary['events'] }}</div></div>
        <div class="stat"><div class="k">Fights</div><div class="v">{{ $summary['fights'] }}</div></div>
        <div class="stat"><div class="k">Graded</div><div class="v green">{{ $summary['settled'] }}</div></div>
        <div class="stat"><div class="k">Bets</div><div class="v">{{ number_format($summary['bets']) }}</div></div>
        <div class="stat"><div class="k">Staked</div><div class="v">{{ number_format($summary['staked']) }}</div></div>
        <div class="stat"><div class="k">Paid out</div><div class="v gold">{{ number_format($summary['paid']) }}</div></div>
        <div class="stat"><div class="k">Refunded</div><div class="v blue">{{ number_format($summary['refunded']) }}</div></div>
        <div class="stat"><div class="k">Net</div><div class="v {{ $summary['net'] >= 0 ? 'green' : 'red' }}">{{ number_format($summary['net']) }}</div></div>
    </div>

    <p class="muted small" style="margin-top:0">
        Net is staked minus paid out and refunded. It is a simulation figure, not cash.
    </p>

    <div class="card" style="margin-top:14px">
        <div class="card-head">
            <h2>{{ $selectedEvent ? 'This event' : 'Per event' }}</h2>
            <span class="muted small">{{ $selectedEvent ? $selectedEvent->name : 'Sort by stake' }}</span>
        </div>

        <div class="table-wrap">
            <table class="table">
                <thead>
                <tr>
                    <th>Event</th>
                    <th>Type</th>
                    <th>Code</th>
                    <th class="num">Bets</th>
                    <th class="num">Staked</th>
                    <th class="num">Paid</th>
                    <th class="num">Refunded</th>
                    <th class="num">Net</th>
                </tr>
                </thead>
                <tbody>
                @forelse ($events as $row)
                    <tr>
                        <td><strong>{{ $row->label }}</strong></td>
                        <td><span class="badge muted">{{ ucfirst($row->type) }}</span></td>
                        <td class="mono small">{{ $row->code }}</td>
                        <td class="num">{{ number_format($row->bets) }}</td>
                        <td class="num">{{ number_format($row->staked) }}</td>
                        <td class="num gold">{{ number_format($row->paid) }}</td>
                        <td class="num">{{ number_format($row->refunded) }}</td>
                        <td class="num {{ $row->net >= 0 ? 'green' : 'red' }}">{{ number_format($row->net) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="muted">No bets have been placed in your events yet.</td></tr>
                @endforelse
                </tbody>
                @if ($events->isNotEmpty())
                    <tfoot>
                    <tr>
                        <th colspan="3">All events</th>
                        <th class="num">{{ number_format($summary['bets']) }}</th>
                        <th class="num">{{ number_format($summary['staked']) }}</th>
                        <th class="num">{{ number_format($summary['paid']) }}</th>
                        <th class="num">{{ number_format($summary['refunded']) }}</th>
                        <th class="num">{{ number_format($summary['net']) }}</th>
                    </tr>
                    </tfoot>
                @endif
            </table>
        </div>
    </div>

    <div class="card" style="margin-top:14px">
        <div class="card-head">
            <h2>Busiest fights</h2>
            <span class="muted small">Top 10 by stake</span>
        </div>

        <div class="table-wrap">
            <table class="table">
                <thead>
                <tr>
                    <th>Fight</th>
                    <th>Event</th>
                    <th>Status</th>
                    <th class="num">Bets</th>
                    <th class="num">Staked</th>
                    <th class="num">Paid</th>
                </tr>
                </thead>
                <tbody>
                @forelse ($fights as $row)
                    <tr>
                        <td><strong>#{{ $row->fight_no }}</strong></td>
                        <td class="muted small">{{ $row->label }}</td>
                        <td><span class="badge {{ $row->status === 'settled' ? 'ok' : 'warn' }}">{{ $row->status }}</span></td>
                        <td class="num">{{ number_format($row->bets) }}</td>
                        <td class="num">{{ number_format($row->staked) }}</td>
                        <td class="num gold">{{ number_format($row->paid) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="muted">No fights yet. Schedule one and open it for betting.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
