@extends('layouts.print')

@php($fight = $bet->fight)
@php($event = $fight?->event)
@php($paid = $bet->paid_out_at ?? now())
@php($percent = $bet->amount_credits > 0 ? ($bet->potential_payout / $bet->amount_credits) * 100 : 0)
@php($serial = str_pad((string) $bet->id, 12, '0', STR_PAD_LEFT))
@php($reference = 'REF-'.str_pad((string) $bet->id, 6, '0', STR_PAD_LEFT).'-'.($bet->placed_at?->format('ymdHis') ?? now()->format('ymdHis')))

@section('title', 'Claim slip #'.$bet->id)
@section('slip_class', 'receipt')
@section('body_class', 'receipt-print')
@section('doc_type', 'CLAIMED')
@section('back_url', route('pos.dashboard'))

@section('body')
    <div class="ticket-brand">{{ $event?->name ?? 'E-SABONG LIVE' }}</div>
    <div class="ticket-title">CLAIMED</div>

    @if ($event?->venue)
        <div class="ticket-line">{{ $event->venue }}</div>
    @endif
    <div class="ticket-line">
        {{ $event?->typeLabel() ?? 'Regular' }} &middot; {{ $event?->starts_at?->format('d M Y') }}
    </div>

    <div class="ticket-gap"></div>

    <div class="ticket-line">REFERENCE NO:</div>
    <div class="ticket-line strong">{{ $reference }}</div>
    <div class="ticket-line">DATE: {{ $paid->format('d M Y') }}</div>
    <div class="ticket-line">TIME: {{ $paid->format('H:i:s') }}</div>
    <div class="ticket-line">CASHIER: {{ $bet->teller?->teller_code ?? '—' }}</div>

    <div class="ticket-gap"></div>

    <div class="ticket-line strong">FIGHT #: {{ $fight?->fight_no }}</div>
    <div class="ticket-line strong">WINNING SIDE: {{ strtoupper(\App\Models\Fight::sideLabel($bet->side)) }}</div>
    <div class="ticket-line strong">BET: {{ number_format($bet->amount_credits, 2) }}</div>
    <div class="ticket-line strong">PAYOUT RATIO: {{ number_format($percent, 2) }}</div>
    <div class="ticket-line strong">WINNING: {{ number_format($bet->potential_payout, 2) }}</div>
    <div class="ticket-line">ACCOUNT: {{ $serial }}</div>

    <div class="ticket-gap"></div>

    <div class="barcode-wrap">
        {!! \App\Support\Barcode::svg($slip['code'], 54, 1.5) !!}
        <div class="barcode-text">{{ $serial }}</div>
        <div class="barcode-code">{{ $slip['code'] }}</div>
    </div>

    <div class="ticket-gap"></div>
    <p class="note center">
        @if ($bet->isPaidOut())
            Paid at the window{{ $bet->paidOutBy ? ' by '.$bet->paidOutBy->name : '' }}.
            This slip cannot be claimed twice.
        @else
            Record the payout on the terminal so this slip cannot be claimed twice.
        @endif
    </p>
    <p class="note center">Printed {{ now()->format('d M Y H:i:s') }}</p>
@endsection
