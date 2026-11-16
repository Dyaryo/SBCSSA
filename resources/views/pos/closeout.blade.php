@extends('layouts.print')

@php($shift = $slip['shift'])
@php($totals = $slip['totals'])

@section('title', 'Shift closeout #'.$shift->id)
@section('slip_class', 'a4')
@section('doc_type', 'SHIFT CLOSEOUT SLIP')
@section('back_url', url()->previous())

@section('body')
    <div class="row">
        <div>
            <div class="row"><span class="k">Slip code</span><span class="v">{{ $slip['code'] }}</span></div>
            <div class="row"><span class="k">Shift</span><span class="v">#{{ $shift->id }} · {{ $shift->status }}</span></div>
        </div>
        <div>
            <div class="row"><span class="k">Opened</span><span class="v">{{ $shift->opened_at?->format('d M Y H:i') }}</span></div>
            <div class="row"><span class="k">Closed</span><span class="v">{{ $shift->closed_at?->format('d M Y H:i') ?? 'still open' }}</span></div>
        </div>
    </div>

    <h2>Teller</h2>
    <div class="row"><span class="k">Code / name</span><span class="v">{{ $shift->teller?->teller_code }} · {{ $shift->teller?->displayName() }}</span></div>
    <div class="row"><span class="k">Game Master</span><span class="v">{{ $shift->teller?->gameMaster?->displayName() ?? '—' }}</span></div>
    <div class="row"><span class="k">Workstation</span><span class="v">{{ $shift->workstation?->code ?? '—' }}</span></div>

    @if ($shift->opening_note || $shift->closing_note || $shift->closed_reason)
        <h2>Notes</h2>
        @if ($shift->opening_note)
            <div class="row"><span class="k">Opening</span><span class="v">{{ $shift->opening_note }}</span></div>
        @endif
        @if ($shift->closing_note)
            <div class="row"><span class="k">Closing</span><span class="v">{{ $shift->closing_note }}</span></div>
        @endif
        @if ($shift->closed_reason)
            <div class="row"><span class="k">Force-close reason</span><span class="v">{{ $shift->closed_reason }}</span></div>
        @endif
    @endif

    <h2>Shift totals</h2>
    <table>
        <tbody>
            <tr><td>Bets accepted</td><td class="num">{{ number_format($totals['bets']) }}</td></tr>
            <tr><td>Staked on meron (red)</td><td class="num">{{ number_format($totals['red_stake']) }}</td></tr>
            <tr><td>Staked on wala (blue)</td><td class="num">{{ number_format($totals['blue_stake']) }}</td></tr>
            <tr><td><strong>Total staked</strong></td><td class="num"><strong>{{ number_format($totals['stake']) }}</strong></td></tr>
            <tr><td>Winning bets / payout credited</td><td class="num">{{ number_format($totals['won']) }} / {{ number_format($totals['payout']) }}</td></tr>
            <tr><td>Losing bets</td><td class="num">{{ number_format($totals['lost']) }}</td></tr>
            <tr><td>Voided / refunded</td><td class="num">{{ number_format($totals['voided']) }} / {{ number_format($totals['refunded']) }}</td></tr>
            <tr><td>Still pending settlement</td><td class="num">{{ number_format($totals['pending']) }}</td></tr>
        </tbody>
        <tfoot>
            <tr><td>Net for the house (payout − stake + refunds)</td><td class="num">{{ number_format($totals['net']) }} cr</td></tr>
        </tfoot>
    </table>

    @if ($totals['pending'] > 0)
        <div class="flag">
            {{ $totals['pending'] }} bet(s) on this shift were still unsettled when it was closed.
            The shift figure is provisional until those fights are settled or voided.
        </div>
    @endif

    <h2>Bets on this shift</h2>
    <table>
        <thead>
            <tr>
                <th>#</th><th>Fight</th><th>Side</th><th class="num">Amount</th>
                <th class="num">Odds</th><th class="num">Payout</th><th>Status</th><th>Time</th>
            </tr>
        </thead>
        <tbody>
        @forelse ($slip['bets'] as $bet)
            <tr>
                <td>{{ $bet->id }}</td>
                <td>{{ $bet->fight?->label() }}</td>
                <td>{{ strtoupper(\App\Models\Fight::sideLabel($bet->side)) }}</td>
                <td class="num">{{ number_format($bet->amount_credits) }}</td>
                <td class="num">{{ number_format($bet->odds_at_placement, 3) }}</td>
                <td class="num">{{ number_format($bet->potential_payout) }}</td>
                <td>{{ $bet->status }}</td>
                <td>{{ $bet->placed_at?->format('H:i:s') }}</td>
            </tr>
        @empty
            <tr><td colspan="8" class="muted">No bets were placed on this shift.</td></tr>
        @endforelse
        </tbody>
    </table>

    <div class="sign">
        <div>Teller signature</div>
        <div>Game Master signature</div>
        <div>Supervisor / date</div>
    </div>

    <p class="note center">Printed {{ $slip['printed_at']->format('d M Y H:i:s') }} · shift #{{ $shift->id }}</p>
@endsection
