@extends('layouts.app')

@section('title', 'Cancel bet #'.$bet->id)
@section('heading', 'Cancel bet #'.$bet->id)
@section('subheading', $teller->teller_code.' · '.$bet->fight?->label())

@section('content')
    <div class="card" style="max-width:620px">
        <div class="card-head">
            <h2>Cancel and refund this bet</h2>
            <a class="small" href="{{ route('pos.dashboard') }}">Back to terminal</a>
        </div>

        <p class="muted small">
            This returns the stake to this terminal's wallet and marks the slip void. The reason is
            recorded for the supervisor. Once betting is locked, only the Game Master can void a bet.
        </p>

        <div class="stats" style="margin:14px 0">
            <div class="stat"><div class="k">Ticket</div><div class="v mono">#{{ $bet->id }}</div></div>
            <div class="stat"><div class="k">Fight</div><div class="v">{{ $bet->fight?->label() }}</div></div>
            <div class="stat"><div class="k">Side</div><div class="v">{{ strtoupper(\App\Models\Fight::sideLabel($bet->side)) }}</div></div>
            <div class="stat"><div class="k">Refund</div><div class="v gold">{{ number_format($bet->amount_credits) }} cr</div></div>
        </div>

        <form method="POST" action="{{ route('pos.bets.cancel', $bet) }}" data-confirm="Cancel bet #{{ $bet->id }} and refund {{ number_format($bet->amount_credits) }} credits?">
            @csrf

            <label class="field">
                <span>Reason for cancellation</span>
                <input class="input" name="reason" value="{{ old('reason') }}" maxlength="200" required
                       placeholder="e.g. wrong side, wrong amount, customer changed bet">
            </label>

            <div class="row" style="gap:8px;margin-top:12px">
                <button class="btn btn-primary">Cancel bet and refund</button>
                <a class="btn btn-ghost" href="{{ route('pos.dashboard') }}">Keep bet</a>
            </div>
        </form>
    </div>
@endsection
