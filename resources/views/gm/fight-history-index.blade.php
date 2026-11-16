@extends('layouts.app')

@section('title', 'Fight history')
@section('heading', 'Fight history')
@section('subheading', 'Every fight in your card with the money on each side and what each side would pay.')

@section('content')
    <div class="card" style="margin-bottom:14px">
        @include('partials.winners-road', [
            'cells' => $road['cells'],
            'cols' => $road['cols'],
            'count' => $road['count'],
        ])
    </div>

    <div class="card">
        <div class="card-head">
            <h2>Fight book</h2>
            <span class="muted small">{{ $fights->total() }} fights</span>
        </div>

        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Timestamp</th>
                        <th>Fight no.</th>
                        <th class="num">Meron value</th>
                        <th class="num">Meron payout</th>
                        <th class="num">Wala value</th>
                        <th class="num">Wala payout</th>
                        <th class="num">Draw value</th>
                        <th class="num">Draw payout</th>
                        <th>Result</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                @forelse ($fights as $fight)
                    <tr>
                        <td class="muted small">{{ \Illuminate\Support\Carbon::parse($fight->created_at)->format('Y-m-d H:i:s') }}</td>
                        <td>
                            <strong>#{{ $fight->fight_no }}</strong>
                            <div class="muted small">{{ $fight->event_label }}</div>
                        </td>
                        <td class="num">{{ number_format($fight->meron_value) }}</td>
                        <td class="num">{{ number_format($fight->meron_payout) }}</td>
                        <td class="num">{{ number_format($fight->wala_value) }}</td>
                        <td class="num">{{ number_format($fight->wala_payout) }}</td>
                        <td class="num">{{ number_format($fight->draw_value) }}</td>
                        <td class="num">{{ number_format($fight->draw_payout) }}</td>
                        <td>
                            <span class="badge {{ $fight->result === 'meron' ? 'ok' : ($fight->result === 'wala' ? 'blue' : 'muted') }}">
                                {{ $fight->result ? ucfirst($fight->result) : '—' }}
                            </span>
                        </td>
                        <td class="muted small">{{ $fight->status }}</td>
                    </tr>
                @empty
                    <tr><td colspan="10" class="muted">No fights yet. Schedule one and open it for betting.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>

        <div class="pagination">{{ $fights->links() }}</div>
    </div>
@endsection
