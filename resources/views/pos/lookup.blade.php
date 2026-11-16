@extends('layouts.app')

@section('title', 'Slip lookup')
@section('heading', 'Slip lookup')
@section('subheading', 'Scan or type the code printed on a bet receipt')

@section('content')
    <div class="card" style="margin-bottom:14px">
        <form method="GET" action="{{ route('pos.lookup') }}" class="row" style="gap:8px;align-items:flex-end">
            <label class="field" style="flex:1;min-width:220px;margin:0">
                <span>Receipt code</span>
                <input class="input mono" name="code" data-scan-input value="{{ $code }}" placeholder="BET-000001-ABCD1234"
                       autofocus autocomplete="off">
            </label>
            <button class="btn btn-primary">Look up</button>
        </form>

        <p class="muted small" style="margin:10px 0 0">
            A handheld scanner types the code straight into this box — press Enter. Press <kbd>F9</kbd> to jump to the
            box from anywhere on the page. If the barcode or QR is damaged, type the MANUAL CODE printed under it.
            The code is checked against the record that produced it, so an altered or invented code will not match.
        </p>
    </div>

    @if ($error)
        <div class="flash err">{{ $error }}</div>
    @endif

    @if ($bet)
        @php($won = $bet->status === 'won')
        @php($refunded = in_array($bet->status, ['void', 'refunded'], true))

        <div class="card">
            <div class="card-head">
                <h2>Bet #{{ $bet->id }} · {{ $bet->fight?->label() }}</h2>
                <span class="badge {{ $won ? 'ok' : ($bet->status === 'lost' ? 'muted' : ($refunded ? 'blue' : 'warn')) }}">{{ $bet->status }}</span>
            </div>

            <div class="stats" style="margin-bottom:14px">
                <div class="stat">
                    <div class="k">Stake</div>
                    <div class="v">{{ number_format($bet->amount_credits) }}</div>
                </div>
                <div class="stat">
                    <div class="k">Side</div>
                    <div class="v {{ $bet->side === 'red' ? 'red' : 'blue' }}">{{ \App\Models\Fight::sideLabel($bet->side) }}</div>
                </div>
                @if ($won)
                    <div class="stat">
                        <div class="k">Payout ratio</div>
                        <div class="v">{{ number_format($bet->amount_credits > 0 ? ($bet->potential_payout / $bet->amount_credits) * 100 : 0, 2) }}</div>
                    </div>
                    <div class="stat">
                        <div class="k">Payout on the slip</div>
                        <div class="v gold">{{ number_format($bet->potential_payout) }}</div>
                    </div>
                @endif
            </div>

            @if ($won && $bet->isPaidOut())
                <div class="flash ok">
                    <strong>Already paid out — {{ number_format($bet->potential_payout) }} credits.</strong>
                    Recorded {{ $bet->paid_out_at->format('d M Y H:i') }}@if ($bet->paidOutBy) by {{ $bet->paidOutBy->name }}@endif.
                    Do not pay this slip again.
                </div>
            @elseif ($won)
                <div class="flash ok">
                    <strong>Pay out {{ number_format($bet->potential_payout) }} credits.</strong>
                    The credits are already in this terminal's wallet from grading — hand the player the amount shown,
                    keep the ticket, then record it so the slip cannot be paid twice.
                </div>

                <form method="POST" action="{{ route('pos.bets.payout', $bet) }}" style="margin-top:10px"
                      data-confirm="Record this payout? The slip is marked paid and cannot be paid again.">
                    @csrf
                    <button class="btn btn-green">Record payout of {{ number_format($bet->potential_payout) }} credits</button>
                </form>
            @elseif ($refunded)
                <div class="flash ok">
                    <strong>Refunded {{ number_format($bet->amount_credits) }} credits.</strong>
                    The fight was voided or cancelled, so the stake went back to this terminal's wallet.
                </div>
            @elseif ($bet->status === 'lost')
                <div class="flash err">
                    <strong>No payout.</strong> This slip backed the losing side.
                </div>
            @else
                <div class="flash err">
                    <strong>Not settled yet.</strong> The fight is still running or waiting to be graded, so this slip
                    has no result. Do not pay out on it.
                </div>
            @endif

            <div class="kv" style="margin-top:12px">
                <dt>Slip code</dt><dd class="mono">{{ $code }}</dd>
                <dt>Placed</dt><dd>{{ $bet->placed_at?->format('d M Y H:i:s') }}</dd>
                <dt>Fight</dt><dd>{{ $bet->fight?->label() }}</dd>
                <dt>Result</dt><dd>{{ $bet->fight?->resultLabel() }}</dd>
                <dt>Paid out</dt><dd>{{ $bet->paid_out_at ? $bet->paid_out_at->format('d M H:i') : '—' }}</dd>
            </div>

            <div class="row" style="margin-top:14px">
                <a class="btn btn-ghost" href="{{ route('pos.bets.receipt', $bet) }}" target="_blank">Open the receipt</a>
                @if ($bet->status === 'won')
                    <a class="btn btn-ghost" href="{{ route('pos.bets.claim', $bet) }}" target="_blank">Claim slip</a>
                @endif
                <a class="btn btn-ghost" href="{{ route('pos.lookup') }}">Look up another slip</a>
            </div>
        </div>
    @endif

    <script src="{{ asset_v('js/scan.js') }}" defer></script>
@endsection
