@extends('mobile.layout')

@section('title', 'Overall Summary Report')

@section('content')
    <div data-live-refresh data-signature="{{ $liveSignature }}" data-feed-url="{{ route('supervisor.mobile.feed') }}"></div>

    <header class="m-head">
        <div class="m-brand"><span class="dot"></span> E-SABONG</div>
        <div class="m-live"><i class="live-dot"></i> Live report</div>
    </header>

    <h1>Overall Summary</h1>
    <p class="m-sub">
        @if ($totalGraded === 0)
            No fight has been graded yet — this report fills in as results are declared.
        @else
            Block {{ $blockNo }} · fights {{ $blockStart }}–{{ $blockEnd }} of {{ $totalGraded }} graded.
            @if ($remaining > 0)
                Next report in {{ $remaining }} fight{{ $remaining === 1 ? '' : 's' }}.
            @else
                Block complete — next fight starts block {{ $blockNo + 1 }}.
            @endif
        @endif
    </p>

    <section class="m-hero">
        <div class="m-hero-label">Total stake (all graded fights)</div>
        <div class="m-hero-value">{{ number_format($overall['stake'], 2) }}</div>
        <div class="m-hero-meta">
            <span>{{ number_format($overall['payout'], 2) }} paid out</span>
            <span>{{ number_format($overall['bets']) }} bets</span>
        </div>
    </section>

    <section class="m-grid">
        <div class="m-tile draw">
            <span class="m-tile-k">Draw</span>
            <strong>{{ number_format($overall['draw']) }}</strong>
            <small>{{ number_format($overall['draw_stake'], 2) }} staked · {{ number_format($overall['draw_payout'], 2) }} paid</small>
        </div>
        <div class="m-tile cancelled">
            <span class="m-tile-k">Cancelled</span>
            <strong>{{ number_format($overall['cancelled']) }}</strong>
            <small>{{ number_format($overall['cancelled_refund'], 2) }} refunded</small>
        </div>
        <div class="m-tile meron">
            <span class="m-tile-k">Meron</span>
            <strong>{{ number_format($overall['meron']) }}</strong>
            <small>winning calls</small>
        </div>
        <div class="m-tile wala">
            <span class="m-tile-k">Wala</span>
            <strong>{{ number_format($overall['wala']) }}</strong>
            <small>winning calls</small>
        </div>
    </section>

    <section class="m-card">
        <div class="m-card-head">
            <h2>This block</h2>
            <span>{{ $totalGraded === 0 ? 'awaiting first result' : 'fights '.$blockStart.'–'.$blockEnd }}</span>
        </div>

        <div class="m-block-stats">
            <div><span>Fights</span><strong>{{ $block['fights'] }}</strong></div>
            <div><span>Stake</span><strong>{{ number_format($block['stake']) }}</strong></div>
            <div><span>Payout</span><strong>{{ number_format($block['payout']) }}</strong></div>
            <div><span>Draw</span><strong>{{ $block['draw'] }}</strong></div>
            <div><span>Cancelled</span><strong>{{ $block['cancelled'] }}</strong></div>
        </div>

        <div class="m-list">
            @forelse ($blockFights as $fight)
                <div class="m-row">
                    <div>
                        <strong>#{{ $fight->fight_no }}</strong>
                        <span>{{ $fight->event?->name }}</span>
                    </div>
                    <span class="m-pill {{ $fight->result }}">{{ strtoupper($fight->result) }}</span>
                </div>
            @empty
                <p class="m-empty">No fight has been graded yet.</p>
            @endforelse
        </div>
    </section>

    <section class="m-card install">
        <h2>Install on your phone</h2>
        <p>
            Open this page in your phone browser and add it to the home screen to use it like an app.
            On Android tap the button below; on iPhone use <strong>Share → Add to Home Screen</strong>.
        </p>
        <button type="button" class="m-btn" data-install-app hidden>Install app</button>
        <p class="m-hint" data-install-hint>On iPhone: Share → Add to Home Screen.</p>
    </section>

    <footer class="m-foot">
        <a href="{{ route('supervisor.reports') }}">Full reports</a>
        <span>Every {{ $blockSize }} fights · refreshes itself</span>
    </footer>
@endsection
