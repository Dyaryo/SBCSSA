@extends('layouts.app')

@section('title', 'Fight console')
@section('heading', $fight->label())
@section('subheading', $fight->event?->name.' · status '.$fight->status.' · odds locked at the time each bet was placed')

@section('content')
    @php($terminal = in_array($fight->status, ['settled', 'void', 'cancelled'], true))
    @php($canGrade = in_array($fight->status, ['betting_locked', 'live'], true))

    <div class="fight" data-gm-fight
         data-signature="{{ $windowSignature }}"
         data-feed-url="{{ route('gm.fights.feed', $fight) }}">
        <div class="fight-top">
            <span class="fight-no">Fight {{ $fight->fight_no }}</span>
            @unless ($terminal)
                <span class="muted small"><i class="live-dot"></i>Bets from the windows appear here as they are taken</span>
            @endunless
            <span class="badge {{ $fight->status === 'settled' ? 'ok' : (in_array($fight->status, ['void', 'cancelled'], true) ? 'err' : 'warn') }}">
                {{ $fight->status }}
            </span>
        </div>

        <div class="sides">
            <div class="side red {{ $fight->isSideLocked('red') ? 'is-locked' : '' }}">
                <div class="name">
                    MERON
                    @if ($fight->isSideLocked('red'))
                        <span class="badge warn">LOCKED</span>
                    @endif
                </div>
                <div class="odds {{ $fight->isCappedFor(\App\Models\Fight::SIDE_RED) ? 'is-capped' : '' }}">{{ number_format($fight->odds_red, 3) }}×</div>
                <div class="odds-label">posted odds</div>
                <div class="odds-effective {{ $fight->isCappedFor(\App\Models\Fight::SIDE_RED) ? 'is-capped' : '' }}">PAYS {{ number_format($fight->effectiveOddsFor(\App\Models\Fight::SIDE_RED), 3) }}×{{ $fight->isCappedFor(\App\Models\Fight::SIDE_RED) ? ' · capped' : '' }}</div>
                <div class="stake">{{ number_format($totals['red']) }} credits staked</div>

                @unless ($terminal)
                    <form method="POST" action="{{ route('gm.fights.side-lock', $fight) }}" class="side-lock">
                        @csrf
                        <input type="hidden" name="side" value="red">
                        <input type="hidden" name="locked" value="{{ $fight->isSideLocked('red') ? 0 : 1 }}">
                        <button class="btn btn-sm {{ $fight->isSideLocked('red') ? 'btn-green' : 'btn-ghost' }}"
                                @disabled($fight->status !== 'betting_open')>
                            {{ $fight->isSideLocked('red') ? 'Unlock Meron' : 'Lock Meron' }}
                        </button>
                    </form>
                @endunless
            </div>
            <div class="vs">VS</div>
            <div class="side blue {{ $fight->isSideLocked('blue') ? 'is-locked' : '' }}">
                <div class="name">
                    WALA
                    @if ($fight->isSideLocked('blue'))
                        <span class="badge warn">LOCKED</span>
                    @endif
                </div>
                <div class="odds {{ $fight->isCappedFor(\App\Models\Fight::SIDE_BLUE) ? 'is-capped' : '' }}">{{ number_format($fight->odds_blue, 3) }}×</div>
                <div class="odds-label">posted odds</div>
                <div class="odds-effective {{ $fight->isCappedFor(\App\Models\Fight::SIDE_BLUE) ? 'is-capped' : '' }}">PAYS {{ number_format($fight->effectiveOddsFor(\App\Models\Fight::SIDE_BLUE), 3) }}×{{ $fight->isCappedFor(\App\Models\Fight::SIDE_BLUE) ? ' · capped' : '' }}</div>
                <div class="stake">{{ number_format($totals['blue']) }} credits staked</div>

                @unless ($terminal)
                    <form method="POST" action="{{ route('gm.fights.side-lock', $fight) }}" class="side-lock">
                        @csrf
                        <input type="hidden" name="side" value="blue">
                        <input type="hidden" name="locked" value="{{ $fight->isSideLocked('blue') ? 0 : 1 }}">
                        <button class="btn btn-sm {{ $fight->isSideLocked('blue') ? 'btn-green' : 'btn-ghost' }}"
                                @disabled($fight->status !== 'betting_open')>
                            {{ $fight->isSideLocked('blue') ? 'Unlock Wala' : 'Lock Wala' }}
                        </button>
                    </form>
                @endunless
            </div>
        </div>

        @if (! $terminal && $fight->status !== 'betting_open')
            <p class="muted small" style="margin:0;text-align:center">Side locks can be set while the betting window is open.</p>
        @endif

        <div class="stats" style="margin-top:14px">
            <div class="stat"><div class="k">Bets</div><div class="v">{{ $totals['count'] }}</div></div>
            <div class="stat"><div class="k">Payouts</div><div class="v gold">{{ number_format($totals['payout']) }}</div></div>
            <div class="stat"><div class="k">Refunded</div><div class="v blue">{{ number_format($totals['refunded']) }}</div></div>
            <div class="stat"><div class="k">Stake</div><div class="v">{{ number_format($totals['red'] + $totals['blue']) }}</div></div>
        </div>

        @if ($fight->isStopRatioActive())
            <div class="cap-note">
                @if ($fight->isCappedFor(\App\Models\Fight::SIDE_RED) || $fight->isCappedFor(\App\Models\Fight::SIDE_BLUE))
                    <strong>Bet stopper {{ number_format($fight->stop_ratio, 3) }}× caps this fight.</strong>
                    @if ($fight->isCappedFor(\App\Models\Fight::SIDE_RED))
                        <span>Meron posted {{ number_format($fight->odds_red, 3) }}× &rarr; pays {{ number_format($fight->effectiveOddsFor(\App\Models\Fight::SIDE_RED), 3) }}×.</span>
                    @endif
                    @if ($fight->isCappedFor(\App\Models\Fight::SIDE_BLUE))
                        <span>Wala posted {{ number_format($fight->odds_blue, 3) }}× &rarr; pays {{ number_format($fight->effectiveOddsFor(\App\Models\Fight::SIDE_BLUE), 3) }}×.</span>
                    @endif
                @else
                    <strong>Bet stopper {{ number_format($fight->stop_ratio, 3) }}× applies.</strong>
                    <span>Both posted odds are below the stopper, so each side pays its posted rate.</span>
                @endif
            </div>
        @endif
    </div>

    <div class="grid two">
        <div class="card">
            <div class="card-head"><h2>Match control</h2></div>

            @if ($terminal)
                <div class="flash {{ $fight->result === 'cancelled' ? 'err' : 'ok' }}" style="margin-bottom:12px">
                    <strong>Result: {{ strtoupper($fight->resultLabel()) }}</strong>
                    @if ($fight->result === 'meron' || $fight->result === 'wala')
                        · {{ strtoupper($fight->result) }} paid {{ number_format($totals['payout']) }} credits
                    @elseif ($fight->isDraw())
                        · every stake refunded ({{ number_format($totals['refunded']) }} credits)
                    @endif
                    @if ($fight->void_reason)
                        <div class="small">{{ $fight->void_reason }}</div>
                    @endif
                </div>

                <p class="muted small">
                    This fight is finalised. If the wrong side was declared, correct it below — the money and
                    the slips are adjusted, and the change is written to the timeline and audit log.
                </p>

                <div class="regrade-box">
                    <div class="spread" style="align-items:baseline">
                        <h3 style="margin:0">Regrade result</h3>
                        <span class="muted small">Currently {{ strtoupper($fight->resultLabel()) }}</span>
                    </div>

                    @if ($regradeBlocked)
                        <p class="muted small" style="margin:10px 0 0">
                            A winning slip on this fight has already been paid out. Regrading is closed once
                            winnings have left the counter.
                        </p>
                    @elseif ($fight->status !== 'settled')
                        <p class="muted small" style="margin:10px 0 0">
                            Only a fight with a declared result can be regraded.
                        </p>
                    @else
                        <p class="muted small" style="margin:8px 0 10px">
                            Tap the correct result. Payouts already credited are reversed first, then the fight is
                            graded again — check the amounts before confirming.
                        </p>

                        <form method="POST" action="{{ route('gm.fights.regrade', $fight) }}"
                              data-confirm="Regrade this fight? Payouts already credited will be reversed and the slips re-settled. This is recorded in the audit log."
                              data-confirm-tone="danger">
                            @csrf

                            <div class="pick-grid four">
                                <button class="pick meron" name="result" value="meron" @disabled($fight->result === 'meron')>
                                    <span class="pick-side">MERON</span>
                                    <span class="pick-meta">correct to meron</span>
                                </button>
                                <button class="pick wala" name="result" value="wala" @disabled($fight->result === 'wala')>
                                    <span class="pick-side">WALA</span>
                                    <span class="pick-meta">correct to wala</span>
                                </button>
                                <button class="pick draw" name="result" value="draw" @disabled($fight->result === 'draw')>
                                    <span class="pick-side">DRAW</span>
                                    <span class="pick-meta">correct to draw</span>
                                </button>
                                <button class="pick cancelled" name="result" value="cancelled">
                                    <span class="pick-side">CANCELLED</span>
                                    <span class="pick-meta">refund every stake</span>
                                </button>
                            </div>

                            <label class="field" style="margin:10px 0 0">
                                <span>Why is it being corrected? (required)</span>
                                <input class="input" name="reason" placeholder="e.g. wrong side announced at the pit" required>
                            </label>
                        </form>
                    @endif
                </div>
            @else
                <div class="row" style="margin-bottom:12px">
                    <form method="POST" action="{{ route('gm.fights.open', $fight) }}">
                        @csrf
                        <button class="btn btn-green" @disabled($fight->status === 'betting_open')>Open fight</button>
                    </form>

                    <form method="POST" action="{{ route('gm.fights.lock', $fight) }}">
                        @csrf
                        <button class="btn btn-primary" @disabled($fight->status !== 'betting_open')>Close fight</button>
                    </form>

                    <form method="POST" action="{{ route('gm.fights.start', $fight) }}">
                        @csrf
                        <button class="btn btn-blue" @disabled($fight->status !== 'betting_locked')>Mark live</button>
                    </form>
                </div>

                <h2 style="margin-top:6px">Declare result</h2>
                <p class="muted small">
                    Tap the winning side. Draw pays the draw tickets at the card's ratio
                    (×{{ number_format($fight->drawRatio(), 3) }}); Cancelled refunds every stake.
                    Grading is final — the money moves as soon as you press the button.
                </p>

                <form method="POST" action="{{ route('gm.fights.result', $fight) }}"
                      data-confirm="Record this result? Payouts and refunds are applied immediately and cannot be undone."
                      data-confirm-tone="danger">
                    @csrf

                    <div class="pick-grid four">
                        <button class="pick meron" name="result" value="meron" @disabled(! $canGrade)>
                            <span class="pick-side">MERON</span>
                            <span class="pick-meta">pays {{ number_format($preview['meron']) }}</span>
                        </button>

                        <button class="pick wala" name="result" value="wala" @disabled(! $canGrade)>
                            <span class="pick-side">WALA</span>
                            <span class="pick-meta">pays {{ number_format($preview['wala']) }}</span>
                        </button>

                        <button class="pick draw" name="result" value="draw" @disabled(! $canGrade)>
                            <span class="pick-side">DRAW</span>
                            <span class="pick-meta">pays {{ number_format($preview['draw']) }}</span>
                        </button>

                        <button class="pick cancelled" name="result" value="cancelled" @disabled(! $canGrade)>
                            <span class="pick-side">CANCELLED</span>
                            <span class="pick-meta">refund {{ number_format($preview['refund']) }}</span>
                        </button>
                    </div>

                    <label class="field" style="margin:10px 0 0">
                        <span>Reason — required only when you press CANCELLED</span>
                        <input class="input" name="reason" placeholder="e.g. fight called off by the referee">
                    </label>
                </form>

                @if (! $canGrade)
                    <p class="muted small" style="margin:10px 0 0">
                        Lock betting (or mark the fight live) before declaring a result.
                    </p>
                @endif

                <div class="spread" style="margin:18px 0 0;align-items:center">
                    <h3 style="margin:0">Fight details</h3>

                    <div class="tv-box" title="What the arena board is showing for this fight">
                        <div class="tv-screen">
                            <span class="tv-label">TV total bets</span>
                            <strong>{{ $tvShare }}%</strong>
                            <small>{{ number_format($tvStake) }} of {{ number_format($tvPool) }} cr</small>
                        </div>
                    </div>
                </div>
                <div class="detail-grid">
                    <div class="mini"><span>Fight no</span><strong>#{{ $fight->fight_no }}</strong></div>
                    <div class="mini"><span>Status</span><strong>{{ $fight->status }}</strong></div>
                    <div class="mini"><span>Meron value</span><strong>{{ number_format($totals['red']) }}</strong></div>
                    <div class="mini"><span>Meron payout</span><strong>{{ number_format($preview['meron']) }}</strong></div>
                    <div class="mini"><span>Wala value</span><strong>{{ number_format($totals['blue']) }}</strong></div>
                    <div class="mini"><span>Wala payout</span><strong>{{ number_format($preview['wala']) }}</strong></div>
                    <div class="mini"><span>Draw value</span><strong>{{ number_format($totals['draw']) }}</strong></div>
                    <div class="mini"><span>Draw payout</span><strong>{{ number_format($preview['draw']) }}</strong></div>
                    <div class="mini"><span>Winning side</span><strong>{{ $fight->result ? strtoupper($fight->resultLabel()) : '—' }}</strong></div>
                </div>
            @endif
        </div>

        <div class="card">
            <div class="card-head">
                <h2>{{ $terminal ? 'Next fight' : 'Fight timeline' }}</h2>
            </div>

            @if ($terminal)
                <p class="muted small">
                    Creates fight <strong>#{{ $nextFightNo }}</strong> in {{ $fight->event?->name }}. The same odds
                    are pre-filled — change them if the next bout is different.
                </p>

                <form method="POST" action="{{ route('gm.fights.next', $fight) }}">
                    @csrf

                    <div class="form-grid">
                        <label class="field">
                            <span>Meron odds</span>
                            <input class="input" type="number" step="0.001" min="1.01" max="20" name="odds_red"
                                   value="{{ number_format((float) $fight->odds_red, 3, '.', '') }}" required>
                        </label>

                        <label class="field">
                            <span>Wala odds</span>
                            <input class="input" type="number" step="0.001" min="1.01" max="20" name="odds_blue"
                                   value="{{ number_format((float) $fight->odds_blue, 3, '.', '') }}" required>
                        </label>
                    </div>

                    <p class="muted small">
                        Stop bet ratio
                        <strong>{{ $fight->event?->hasStopRatio() ? number_format($fight->event->stop_ratio, 3).'×' : 'not set' }}</strong>
                        — taken from the event and applied to this fight automatically.
                    </p>

                    <label class="field">
                        <span>Schedule (optional)</span>
                        <input class="input" type="datetime-local" name="scheduled_at">
                    </label>

                    <button class="btn btn-green btn-block">Create fight #{{ $nextFightNo }}</button>
                </form>

                <div class="table-wrap" style="margin-top:16px">
                    <table class="table">
                        <thead><tr><th>When</th><th>Kind</th><th>Note</th></tr></thead>
                        <tbody>
                        @forelse ($fight->logs->sortByDesc('id')->take(6) as $log)
                            <tr>
                                <td class="muted small">{{ $log->created_at?->format('d M H:i:s') }}</td>
                                <td><span class="badge muted">{{ $log->kind }}</span></td>
                                <td class="small">{{ $log->note }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="muted">No timeline entries.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            @else
                <div class="table-wrap">
                    <table class="table">
                        <thead><tr><th>When</th><th>Kind</th><th>Note</th></tr></thead>
                        <tbody>
                        @forelse ($fight->logs->sortByDesc('id') as $log)
                            <tr>
                                <td class="muted small">{{ $log->created_at?->format('d M H:i:s') }}</td>
                                <td><span class="badge muted">{{ $log->kind }}</span></td>
                                <td class="small">{{ $log->note }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="muted">No timeline entries.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
            @endif

            <div class="kv" style="margin-top:12px">
                <dt>Bet stopper ratio</dt><dd>{{ $fight->isStopRatioActive() ? number_format($fight->stop_ratio, 3).'× (payout cap)' : 'none — fixed odds only' }}</dd>
                <dt>Payout display</dt><dd>{{ $fight->event?->payout_display === 'whole' ? 'Whole numbers' : 'With decimals' }}</dd>
                <dt>Draw ratio</dt><dd>{{ number_format($fight->drawRatio(), 3) }}×</dd>
                <dt>TV total bets</dt><dd>{{ $fight->event?->tv_total_bets ?? 100 }}% of the pool</dd>
                <dt>Bomba</dt><dd>{{ number_format((int) ($fight->event?->bomba_amount ?? 0)) }} cr · break {{ $fight->event?->bomba_breakdown ?? 3 }} · every {{ $fight->event?->bomba_interval ?? 30 }}s</dd>
                <dt>Result</dt><dd>{{ $fight->resultLabel() }}</dd>
                <dt>Declared by</dt><dd>{{ $fight->declaredBy?->name ?? '—' }}</dd>
                <dt>Declared at</dt><dd>{{ $fight->declared_at?->format('d M Y H:i') ?? '—' }}</dd>
                <dt>Scheduled</dt><dd>{{ $fight->scheduled_at?->format('d M Y H:i') ?? '—' }}</dd>
            </div>
        </div>
    </div>

    @unless ($terminal)
        <script src="{{ asset_v('js/gm-fight.js') }}" defer></script>
    @endunless

    <div class="card" style="margin-top:14px">
        <div class="card-head">
            <h2>Bets on this fight</h2>
            <a class="small" href="{{ route('gm.fights.history', $fight) }}">Full bet history →</a>
        </div>

        <div class="table-wrap">
            <table class="table">
                <thead><tr><th>#</th><th>Teller</th><th>Side</th><th class="num">Amount</th><th class="num">Posted odds</th><th class="num">Pays at the counter</th><th>Status</th><th>Placed</th></tr></thead>
                <tbody>
                @forelse ($bets as $bet)
                    <tr class="{{ $bet->status === 'won' ? 'win-row' : '' }}">
                        <td class="mono">{{ $bet->id }}</td>
                        <td>{{ $bet->teller?->teller_code }}<div class="muted small">{{ $bet->teller?->displayName() }}</div></td>
                        <td><span class="badge {{ $bet->side === 'red' ? 'red' : 'blue' }}">{{ \App\Models\Fight::sideLabel($bet->side) }}</span></td>
                        <td class="num">{{ number_format($bet->amount_credits) }}</td>
                        <td class="num mono">{{ number_format($bet->odds_at_placement, 3) }}</td>
                        <td class="num">
                            @if ($bet->status === 'won')
                                <span class="payout-now">{{ number_format($bet->potential_payout) }}</span>
                                <div class="small payout-note">hand over at window</div>
                            @elseif (in_array($bet->status, ['void', 'refunded'], true))
                                <span class="muted">{{ number_format($bet->amount_credits) }}</span>
                                <div class="small muted">stake refunded</div>
                            @elseif ($bet->status === 'lost')
                                <span class="muted">—</span>
                                <div class="small muted">losing slip</div>
                            @else
                                <span class="muted">{{ number_format($bet->potential_payout) }}</span>
                                <div class="small muted">pending result</div>
                            @endif
                        </td>
                        <td><span class="badge {{ $bet->status === 'won' ? 'ok' : ($bet->status === 'lost' ? 'muted' : 'warn') }}">{{ $bet->status }}</span></td>
                        <td class="muted small">{{ $bet->placed_at?->format('d M H:i:s') }}</td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="muted">No bets on this fight.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
