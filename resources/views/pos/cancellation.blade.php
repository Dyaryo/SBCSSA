@extends('layouts.print')

@php($fight = $bet->fight)
@php($event = $fight?->event)
@php($cancelledAt = $bet->settled_at ?? now())
@php($serial = str_pad((string) $bet->id, 12, '0', STR_PAD_LEFT))

@section('title', 'Cancellation slip #'.$bet->id)
@section('slip_class', 'receipt')
@section('body_class', 'receipt-print')
@section('doc_type', 'CANCELLED')
@section('back_url', route('pos.dashboard'))

@section('body')
    <div class="ticket-brand">{{ $event?->name ?? 'CAMSUR SPORTS ARENA' }}</div>
    <div class="ticket-title">CANCELLED</div>

    @if ($event?->venue)
        <div class="ticket-line">{{ $event->venue }}</div>
    @endif
    <div class="ticket-line">
        {{ $event?->typeLabel() ?? 'Regular' }} &middot; {{ $event?->starts_at?->format('d M Y') }}
    </div>

    <div class="ticket-gap"></div>

    <div class="ticket-line strong">FIGHT # {{ $fight?->fight_no }}</div>
    <div class="ticket-line strong">REFUNDED: {{ number_format($bet->amount_credits, 2) }}</div>
    <div class="ticket-line strong">BET TYPE: {{ strtoupper(\App\Models\Fight::sideLabel($bet->side)) }}</div>

    <div class="ticket-gap"></div>

    <div class="ticket-line">DATE: {{ $cancelledAt->format('d M Y') }}</div>
    <div class="ticket-line">TIME: {{ $cancelledAt->format('H:i:s') }}</div>
    <div class="ticket-line">CASHIER: {{ $bet->teller?->teller_code ?? '—' }}</div>
    <div class="ticket-line">TERMINAL: {{ $bet->shift?->workstation?->code ?? '—' }}</div>

    <div class="ticket-gap"></div>

    <div class="ticket-line">REASON</div>
    <div class="ticket-line strong">{{ $bet->void_reason ?: '—' }}</div>

    <div class="ticket-gap"></div>
    <div class="ticket-line">ORIGINAL TICKET: {{ $bet->id }}</div>
    <div class="ticket-line">PLACED: {{ $bet->placed_at?->format('d M Y H:i:s') ?? '—' }}</div>

    <div class="ticket-gap"></div>

    <div class="barcode-wrap">
        {!! \App\Support\Barcode::svg($slip['code'], 54, 1.5) !!}
        <div class="barcode-text">{{ $serial }}</div>
        <div class="barcode-manual">MANUAL CODE</div>
        <div class="barcode-code">{{ $slip['code'] }}</div>
    </div>

    <div class="ticket-gap"></div>
    <p class="note center">Printed {{ now()->format('d M Y H:i:s') }}</p>
@endsection
