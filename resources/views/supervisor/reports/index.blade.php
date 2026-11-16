@extends('layouts.app')

@section('title', 'Reports')
@section('heading', 'Reports')
@section('subheading', 'Generate a report from live data, export it as CSV, or seal it into a hash-chained snapshot.')

@section('content')
    <div class="card" style="margin-bottom:14px">
        <div class="card-head"><h2>Report builder</h2></div>

        <form method="GET" action="{{ route('supervisor.reports') }}">
            <div class="form-grid">
                <label class="field">
                    <span>Report type</span>
                    <select class="input" name="scope">
                        @foreach ($scopes as $key => $label)
                            <option value="{{ $key }}" @selected($scope === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>

                <label class="field">
                    <span>Fight</span>
                    <select class="input" name="fight_id">
                        <option value="">—</option>
                        @foreach ($fights as $fight)
                            <option value="{{ $fight->id }}" @selected(request('fight_id') == $fight->id)>#{{ $fight->id }} · {{ $fight->label() }}</option>
                        @endforeach
                    </select>
                </label>

                <label class="field">
                    <span>Event</span>
                    <select class="input" name="event_id">
                        <option value="">—</option>
                        @foreach ($events as $event)
                            <option value="{{ $event->id }}" @selected(request('event_id') == $event->id)>{{ $event->name }}</option>
                        @endforeach
                    </select>
                </label>

                <label class="field">
                    <span>Shift</span>
                    <select class="input" name="shift_id">
                        <option value="">—</option>
                        @foreach ($shifts as $shift)
                            <option value="{{ $shift->id }}" @selected(request('shift_id') == $shift->id)>
                                #{{ $shift->id }} · {{ $shift->teller?->teller_code }}
                            </option>
                        @endforeach
                    </select>
                </label>

                <label class="field">
                    <span>Teller</span>
                    <select class="input" name="teller_id">
                        <option value="">—</option>
                        @foreach ($tellers as $teller)
                            <option value="{{ $teller->id }}" @selected(request('teller_id') == $teller->id)>{{ $teller->teller_code }}</option>
                        @endforeach
                    </select>
                </label>

                <label class="field">
                    <span>Game Master</span>
                    <select class="input" name="game_master_id">
                        <option value="">—</option>
                        @foreach ($gameMasters as $gm)
                            <option value="{{ $gm->id }}" @selected(request('game_master_id') == $gm->id)>{{ $gm->displayName() }}</option>
                        @endforeach
                    </select>
                </label>

                <label class="field"><span>Date</span><input class="input" type="date" name="date" value="{{ request('date', now()->toDateString()) }}"></label>
                <label class="field"><span>Month</span><input class="input" type="month" name="month" value="{{ request('month', now()->format('Y-m')) }}"></label>
            </div>

            <div class="row">
                <button class="btn btn-primary">Generate</button>
                <a class="btn btn-ghost" href="{{ route('supervisor.reports.snapshots') }}">Sealed snapshots</a>
            </div>
        </form>
    </div>

    @if ($error)
        <div class="flash err">{{ $error }}</div>
    @endif

    @if ($report)
        @php($payload = $report['payload'])

        <div class="card">
            <div class="card-head">
                <h2>{{ $payload['title'] }}</h2>
                <div class="row">
                    <a class="btn btn-sm btn-ghost" href="{{ route('supervisor.reports.csv', request()->query()) }}">Export CSV</a>
                    <form method="POST" action="{{ route('supervisor.reports.snapshot') }}" class="inline-form">
                        @csrf
                        @foreach (request()->query() as $key => $value)
                            <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                        @endforeach
                        <button class="btn btn-sm btn-green">Seal snapshot</button>
                    </form>
                </div>
            </div>

            <div class="grid two">
                <div class="card" style="background:#0b1119">
                    <div class="kv">
                        <dt>Scope</dt><dd>{{ $report['scope'] }}</dd>
                        <dt>Reference</dt><dd>{{ $report['scope_ref_id'] ?? 'system-wide' }}</dd>
                        <dt>Period</dt><dd>{{ $report['period_start'] }} → {{ $report['period_end'] }}</dd>
                    </div>
                </div>

                <div class="card" style="background:#0b1119">
                    <div class="kv">
                        @foreach ($payload['totals'] ?? [] as $key => $value)
                            <dt>{{ ucwords(str_replace('_', ' ', $key)) }}</dt>
                            <dd class="num" style="text-align:left">{{ is_numeric($value) ? number_format((int) $value) : $value }}</dd>
                        @endforeach
                    </div>
                </div>
            </div>

            @if (! empty($payload['event']))
                <div class="card" style="background:#0b1119;margin-top:14px">
                    <div class="card-head"><h2>Card settings</h2></div>
                    <div class="kv">
                        @foreach ($payload['event'] as $key => $value)
                            <dt>{{ ucwords(str_replace('_', ' ', $key)) }}</dt>
                            <dd>{{ is_numeric($value) ? number_format((int) $value) : ($value ?? '—') }}</dd>
                        @endforeach
                    </div>
                </div>
            @endif

            @if (! empty($payload['cards']))
                <div class="card" style="background:#0b1119;margin-top:14px">
                    <div class="card-head"><h2>Cards by this Game Master</h2></div>

                    <div class="table-wrap">
                        <table class="table">
                            <thead>
                                <tr>
                                    @foreach (array_keys($payload['cards'][0]) as $column)
                                        <th>{{ ucwords(str_replace('_', ' ', $column)) }}</th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                            @foreach ($payload['cards'] as $row)
                                <tr>
                                    @foreach ($row as $value)
                                        <td>{{ $value ?? '—' }}</td>
                                    @endforeach
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif

            @if (! empty($payload['rows']))
                <div class="table-wrap" style="margin-top:14px">
                    <table class="table">
                        <thead>
                            <tr>
                                @foreach (array_keys($payload['rows'][0]) as $column)
                                    <th>{{ ucwords(str_replace('_', ' ', $column)) }}</th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody>
                        @foreach ($payload['rows'] as $row)
                            <tr>
                                @foreach ($row as $value)
                                    <td>{{ $value ?? '—' }}</td>
                                @endforeach
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="empty" style="margin-top:14px">This report has no line items.</div>
            @endif
        </div>
    @elseif (! $error)
        <div class="empty">Choose a report type and press <strong>Generate</strong>.</div>
    @endif
@endsection
