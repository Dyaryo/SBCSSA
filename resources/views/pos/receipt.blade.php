@extends('layouts.print')

@php($bet = $slip['bet'])
@php($fight = $bet->fight)
@php($event = $fight?->event)
@php($placed = $bet->placed_at ?? now())
@php($serial = str_pad((string) $bet->id, 12, '0', STR_PAD_LEFT))

@section('title', 'Bet receipt #'.$bet->id)
@section('slip_class', 'receipt')
@section('body_class', 'receipt-print')
@section('doc_type', 'BET RECEIPT')
@section('back_url', route('pos.dashboard'))

@section('body')
    <div class="ticket-brand">{{ $event?->name ?? 'E-SABONG LIVE' }}</div>
    <div class="ticket-title">BET</div>

    @if ($event?->venue)
        <div class="ticket-line">{{ $event->venue }}</div>
    @endif
    <div class="ticket-line">
        {{ $event?->typeLabel() ?? 'Regular' }} &middot; {{ $event?->starts_at?->format('d M Y') }}
    </div>

    <div class="ticket-gap"></div>

    <div class="ticket-line strong">FIGHT # {{ $fight?->fight_no }}</div>
    <div class="ticket-line strong">BET AMOUNT: {{ number_format($bet->amount_credits, 2) }}</div>
    <div class="ticket-line strong">BET TYPE: {{ strtoupper(\App\Models\Fight::sideLabel($bet->side)) }}</div>

    <div class="ticket-gap"></div>

    <div class="ticket-line">DATE: {{ $placed->format('d M Y') }}</div>
    <div class="ticket-line">TIME: {{ $placed->format('H:i:s') }}</div>
    <div class="ticket-line">CASHIER: {{ $bet->teller?->teller_code ?? '—' }}</div>
    <div class="ticket-line">TERMINAL: {{ $bet->shift?->workstation?->code ?? '—' }}</div>

    @if ($bet->void_reason)
        <div class="ticket-line">VOIDED: {{ $bet->void_reason }}</div>
    @endif

    <div class="ticket-gap"></div>

    <div class="barcode-wrap">
        {!! \App\Support\Barcode::svg($slip['code'], 54, 1.5) !!}
        <div class="barcode-text">{{ $serial }}</div>
        <div class="barcode-manual">MANUAL CODE</div>
        <div class="barcode-code">{{ $slip['code'] }}</div>
    </div>

    <div class="ticket-qr">
        {!! $slip['qr_svg'] !!}
    </div>

    <div class="ticket-gap"></div>
    <p class="note center">
        Printed {{ $slip['printed_at']->format('d M Y H:i:s') }}
    </p>
@endsection
