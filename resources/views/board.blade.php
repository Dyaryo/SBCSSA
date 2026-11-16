<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>E-Sabong — Arena Board</title>
    <link rel="icon" href="{{ asset_v('favicon.ico') }}" sizes="any">
    <link rel="icon" href="{{ asset_v('icons/rooster-mascot.svg') }}" type="image/svg+xml">
    <link rel="stylesheet" href="{{ asset_v('css/board.css') }}">
</head>
<body>
<div class="board" data-big-bet-feed="{{ $bigBetFeed }}" data-big-bet-token="{{ $bigBetToken }}"
     data-board-signature="{{ $boardSignature }}">
    <header class="board-top">
        <div class="clock">
            <span class="time" data-board-clock>{{ now()->format('g:i:s A') }}</span>
            <span class="date">{{ now()->format('D d M Y') }}</span>
        </div>

        <div class="venue">
            <strong>{{ strtoupper($event?->venue ?? 'E-SABONG VIRTUAL CONSOLE') }}</strong>
            <span>{{ $event?->name ?? 'No event open' }}</span>
            <small>{{ $event?->starts_at?->format('F d, Y') ?? now()->format('F d, Y') }}</small>
            <em>Minimum bet {{ number_format($minimum) }}</em>
        </div>

        <div class="status {{ $status['class'] }}">{{ $status['label'] }}</div>
    </header>

    <div class="pool-line">
        <div class="pool-label">
            Total pool
            @if ($fight)<span>Fight #{{ $fight->fight_no }}</span>@endif
        </div>
        <div class="pool-amount">{{ number_format($stakes['meron'] + $stakes['wala'] + $stakes['draw'], 2) }}</div>
        <div class="pool-sub">
            Meron {{ number_format($stakes['meron'], 2) }}
            <i></i> Wala {{ number_format($stakes['wala'], 2) }}
            <i></i> Draw {{ number_format($stakes['draw'], 2) }}
            <b>Total payout {{ number_format($payouts['meron'] + $payouts['wala'] + $payouts['draw'], 2) }}</b>
        </div>
    </div>

    <div class="board-play-only">
        <strong>Play money only</strong>
        <span>Virtual credits have no cash value · no purchase, cash-in or cash payout</span>
    </div>

    <div class="board-grid">
        <aside class="results">
            <h2>Results</h2>

            <ul class="counts">
                <li class="meron"><span>MERON</span><b>{{ $counts['meron'] }}</b></li>
                <li class="wala"><span>WALA</span><b>{{ $counts['wala'] }}</b></li>
                <li class="draw"><span>DRAW</span><b>{{ $counts['draw'] }}</b></li>
                <li class="cancelled"><span>CANCELLED</span><b>{{ $counts['cancelled'] }}</b></li>
            </ul>

            <div class="mini">
                <div class="mini-head"><span>Fight #</span><span>Winner</span></div>
                @forelse ($results->take(8) as $row)
                    <div class="mini-row">
                        <span>{{ $row->fight_no }}</span>
                        <span class="w {{ $row->result }}">{{ strtoupper($row->result) }}</span>
                    </div>
                @empty
                    <div class="mini-row"><span>—</span><span class="w">—</span></div>
                @endforelse
            </div>
        </aside>

        <main class="stage">
            <section class="side meron {{ $fight?->isSideLocked('red') ? 'locked' : '' }}">
                <div class="side-label">MERON</div>
                <div class="side-amount">{{ number_format($stakes['meron'], 2) }}</div>
                <div class="side-payout">Total payout <b>{{ number_format($payouts['meron'], 2) }}</b></div>
                @if ($fight?->isSideLocked('red'))
                    <div class="side-locked">LOCKED</div>
                @endif
            </section>

            <section class="center">
                <div class="draw">
                    <span class="box-title">DRAW</span>
                    <strong>1–{{ number_format($drawRatio, 0) }}</strong>
                    <span class="box-value">{{ number_format($stakes['draw'], 2) }}</span>
                    <small>payout {{ number_format($payouts['draw'], 2) }}</small>
                </div>
                <div class="fight-no">
                    <span class="box-title">FIGHT #</span>
                    <strong>{{ $fight?->fight_no ?? '—' }}</strong>
                </div>
            </section>

            <section class="side wala {{ $fight?->isSideLocked('blue') ? 'locked' : '' }}">
                <div class="side-label">WALA</div>
                <div class="side-amount">{{ number_format($stakes['wala'], 2) }}</div>
                <div class="side-payout">Total payout <b>{{ number_format($payouts['wala'], 2) }}</b></div>
                @if ($fight?->isSideLocked('blue'))
                    <div class="side-locked">LOCKED</div>
                @endif
            </section>
        </main>
    </div>

    <section class="tv-display" title="The share of the pool the TV board is showing">
        <div class="tv-head">
            <span class="tv-on">● ON AIR</span>
            <span class="tv-share">TV total bets <strong>{{ $tvShare }}%</strong></span>
            <span class="tv-shown">{{ number_format($tvStake, 2) }} cr shown</span>
            <span class="tv-winners">Winners road · {{ $results->count() }} result(s)</span>
        </div>

        <div class="road" style="--cols: {{ $roadCols }}">
            @foreach ($roadCells as $roadRow)
                @foreach ($roadRow as $cell)
                    <div class="road-cell">
                        @if ($cell)
                            <span class="road-mark {{ $cell->result }}"
                                  title="Fight #{{ $cell->fight_no }} — {{ strtoupper($cell->result) }}"></span>
                        @endif
                    </div>
                @endforeach
            @endforeach
        </div>
    </section>

    <footer class="board-foot">
        <div class="board-meta">
            <span>Minimum bet <strong>{{ number_format($minimum) }}</strong></span>
            <span>Total value <strong>{{ number_format($stakes['meron'] + $stakes['wala'] + $stakes['draw'], 2) }}</strong></span>
            <span>Total payout <strong>{{ number_format($payouts['meron'] + $payouts['wala'] + $payouts['draw'], 2) }}</strong></span>
        </div>

        <span class="beads-legend">
            <i class="road-mark meron"></i> meron
            <i class="road-mark wala"></i> wala
            <i class="road-mark draw"></i> draw
            <i class="road-mark cancelled"></i> cancelled
        </span>
    </footer>
</div>

<script src="{{ asset_v('js/board.js') }}"></script>
</body>
</html>
