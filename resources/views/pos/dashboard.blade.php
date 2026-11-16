@extends('layouts.app')

@section('title', 'Pay in & pay out')
@section('body-class', 'pos-till')
@section('heading', 'Pay in & pay out')
@section('subheading', $teller->teller_code.' · '.$teller->displayName().' · '.($workstation?->code ?? 'terminal unbound'))

@section('content')
    @if (session('print_receipt'))
        <div data-auto-print="{{ session('print_receipt') }}" hidden></div>
    @endif

    @if (session('print_claim'))
        <div data-auto-print="{{ session('print_claim') }}" hidden></div>
    @endif

    @if (session('print_cancellation'))
        <div data-auto-print="{{ session('print_cancellation') }}" hidden></div>
    @endif

    @if (session('bet_success'))
        @php($success = session('bet_success'))
        <div class="bet-success" data-bet-success role="status" aria-live="polite">
            <div class="bet-success-card">
                <div class="bet-success-tick" aria-hidden="true">
                    <svg viewBox="0 0 52 52"><circle cx="26" cy="26" r="23"/><path d="M15 27l7 7 15-16"/></svg>
                </div>
                <div class="bet-success-kicker">Bet accepted · Ticket #{{ $success['id'] }}</div>
                <div class="bet-success-amount">{{ number_format($success['amount']) }}</div>
                <div class="bet-success-meta">{{ $success['side'] }} · {{ $success['fight'] }}</div>
            </div>
        </div>
    @endif

    <div class="pos-terminal">
        <div class="pos-main" data-pos-live
             data-signature="{{ $windowSignature }}"
             data-fight-signature="{{ $fightSignature }}"
             data-feed-url="{{ route('pos.fights.feed') }}"
             data-window-url="{{ route('pos.fights.window') }}"
             data-shift-id="{{ $shift?->id }}"
             data-scan-url="{{ route('pos.lookup') }}">
            <div class="row fight-heading">
                <h2 style="margin:0">Pay in — open fights</h2>
                <span class="muted small">
                    <i class="live-dot"></i>Updates itself when the pit opens or closes a fight
                </span>
            </div>

            <div class="pos-fight-stack" data-pos-fight-stack aria-live="polite">
                @include('pos._open_fights', ['openFights' => $openFights, 'shift' => $shift])
            </div>

            <div class="card" style="margin-bottom:14px">
                <div class="card-head">
                    <h2>Pay out — scan a slip</h2>
                    <a class="small" href="{{ route('pos.lookup') }}">Open the scanner page →</a>
                </div>
                <form method="GET" action="{{ route('pos.lookup') }}" class="row" style="gap:8px">
                    <input class="input mono" name="code" data-scan-input placeholder="Scan or type the receipt code (BET-…)"
                           style="flex:1;min-width:220px" autocomplete="off">
                    <button class="btn btn-ghost">Look up</button>
                </form>

                <p class="pos-keys" style="margin:8px 0 0">
                    <span><kbd>F9</kbd> jump to the scan box</span>
                    <span>a handheld scanner types the code and presses Enter</span>
                </p>
            </div>

            <div class="card" style="margin-bottom:14px">
                @if (! $shift)
                    <div class="card-head"><h2>Open your shift</h2></div>
                    <p class="muted small">Bets can only be accepted while a shift is open. Shift totals are sealed into a report when you close.</p>

                    <form method="POST" action="{{ route('pos.shift.open') }}">
                        @csrf
                        <div class="form-grid">
                            <label class="field"><span>Opening note (optional)</span><input class="input" name="opening_note" placeholder="Counter 1, morning shift"></label>
                        </div>
                        <button class="btn btn-green">Open shift</button>
                    </form>
                @else
                    <div class="spread">
                        <div>
                            <h2>Shift #{{ $shift->id }} open</h2>
                            <p class="muted small" style="margin:0">
                                Opened {{ $shift->opened_at?->format('d M Y H:i') }} · {{ $shift->bets()->count() }} bets ·
                                {{ number_format((int) $shift->bets()->sum('amount_credits')) }} credits staked
                            </p>
                        </div>

                        <form method="POST" action="{{ route('pos.shift.close') }}" class="row" style="gap:6px" data-confirm="Close this shift and seal the report?">
                            @csrf
                            <input class="input" name="closing_note" placeholder="closing note" style="width:180px">
                            <button class="btn btn-ghost">Close shift</button>
                        </form>

                        <a class="btn btn-sm btn-ghost" href="{{ route('pos.shifts.closeout', $shift) }}" target="_blank">Closeout slip</a>
                    </div>
                @endif
            </div>

            <div class="card">
                <div class="card-head">
                    <h2>My latest bets</h2>
                    <a class="small" href="{{ route('pos.history') }}">Full history →</a>
                </div>

                <div class="table-wrap">
                    <table class="table">
                        <thead><tr><th>#</th><th>Fight</th><th>Side</th><th class="num">Amount</th><th>Status</th><th>Time</th><th></th></tr></thead>
                        <tbody>
                        @forelse ($recent as $bet)
                            <tr>
                                <td class="mono">{{ $bet->id }}</td>
                                <td class="small">{{ $bet->fight?->label() }}</td>
                                <td><span class="badge {{ $bet->side === 'red' ? 'red' : ($bet->side === 'blue' ? 'blue' : 'draw') }}">{{ \App\Models\Fight::sideLabel($bet->side) }}</span></td>
                                <td class="num">{{ number_format($bet->amount_credits) }}</td>
                                <td><span class="badge {{ $bet->status === 'won' ? 'ok' : ($bet->status === 'lost' ? 'muted' : 'warn') }}">{{ $bet->status }}</span></td>
                                <td class="muted small">{{ $bet->placed_at?->format('H:i:s') }}</td>
                                <td class="small">
                                    <a href="{{ route('pos.bets.receipt', $bet) }}" target="_blank">slip</a>
                                    @if ($bet->status === \App\Models\Bet::STATUS_ACTIVE && $bet->fight?->status === \App\Models\Fight::STATUS_BETTING_OPEN)
                                        · <a href="{{ route('pos.bets.cancel.form', $bet) }}">cancel</a>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="7" class="muted">No bets placed yet.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <aside class="pos-rail">
            <div class="card rail-payout">
                <div class="card-head rail-payout-head">
                    <h2>Pay out — winning slips to hand over</h2>
                    <span class="muted small">{{ $payoutsDue->count() }} unpaid</span>
                </div>

                @if ($payoutsDue->isEmpty())
                    <p class="muted small" style="margin:0">Nothing to pay out. Graded winning slips appear here automatically.</p>
                @else
                    <div class="table-wrap">
                        <table class="table">
                            <thead><tr><th>Slip</th><th class="num">Pay</th></tr></thead>
                            <tbody>
                            @foreach ($payoutsDue as $row)
                                <tr class="win-row">
                                    <td>
                                        <span class="mono">{{ $row['code'] }}</span>
                                        <span class="muted small">#{{ $row['bet']->fight?->fight_no }} · {{ number_format($row['bet']->amount_credits) }}</span>
                                    </td>
                                    <td class="num"><span class="payout-now">{{ number_format($row['bet']->potential_payout) }}</span></td>
                                </tr>
                                <tr>
                                    <td colspan="2" class="small">
                                        <a href="{{ route('pos.lookup', ['code' => $row['code']]) }}">open &amp; pay</a>
                                        · <a href="{{ route('pos.bets.claim', $row['bet']) }}" target="_blank">claim slip</a>
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>

            <div class="rail-card rail-facts">
                <div class="fact"><span>Credits available</span><strong class="gold">{{ number_format($wallet->balance_credits) }}</strong></div>
                <div class="fact"><span>Shift</span><strong class="{{ $shift ? 'green' : 'red' }}">{{ $shift ? 'OPEN #'.$shift->id : 'CLOSED' }}</strong></div>
                <div class="fact"><span>Workstation</span><strong>{{ $workstation?->code ?? '—' }}</strong></div>
                <div class="fact"><span>Bets this session</span><strong>{{ $shift ? $shift->bets()->count() : 0 }}</strong></div>
                <div class="fact"><span>Payouts to hand over</span><strong class="gold">{{ number_format($payoutsDue->sum(fn ($row) => $row['bet']->potential_payout)) }}</strong></div>
            </div>

            <div class="rail-card">
                <h3>Transaction</h3>
                @php($last = $recent->first())
                @if ($last)
                    <div class="kv" style="font-size:12.5px">
                        <dt>Ticket</dt><dd class="mono">#{{ $last->id }}</dd>
                        <dt>Bet</dt><dd>{{ $last->fight ? 'Fight #'.$last->fight->fight_no : '—' }}</dd>
                        <dt>Type</dt><dd>{{ strtoupper(\App\Models\Fight::sideLabel($last->side)) }}</dd>
                        <dt>Amount</dt><dd>{{ number_format($last->amount_credits) }}</dd>
                        <dt>Time</dt><dd>{{ $last->placed_at?->format('H:i:s') }}</dd>
                        <dt>Shift</dt><dd class="mono">#{{ $last->shift_session_id ?? '—' }}</dd>
                    </div>
                @else
                    <p class="muted small" style="margin:0">No bet taken yet on this terminal.</p>
                @endif
            </div>

            <div class="rail-card">
                <h3>Recent tickets</h3>
                @if ($recent->isEmpty())
                    <p class="muted small" style="margin:0">Tickets appear here as you take bets.</p>
                @else
                    <table class="rail-table">
                        <tbody>
                        @foreach ($recent->take(6) as $bet)
                            <tr>
                                <td class="mono">#{{ $bet->id }}</td>
                                <td>{{ strtoupper(\App\Models\Fight::sideLabel($bet->side)) }}</td>
                                <td class="num">{{ number_format($bet->amount_credits) }}</td>
                                <td class="rail-print">
                                    <a href="{{ route('pos.bets.receipt', $bet) }}" target="_blank">PRINT</a>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                @endif
            </div>

            <div class="rail-card rail-total">
                <span class="muted small">TOTAL BETS this shift</span>
                <strong>{{ $shift ? number_format($shift->bets()->count()) : 0 }} slips</strong>
                <strong>{{ number_format($shift ? (int) $shift->bets()->sum('amount_credits') : 0) }} cr</strong>
            </div>
        </aside>
    </div>

    <script src="{{ asset_v('js/pos.js') }}" defer></script>
    <script src="{{ asset_v('js/scan.js') }}" defer></script>
@endsection
