{{-- Open betting cards. Re-rendered by /pos/fights-window so a new fight
     slides in without a full page reload. --}}
            @forelse ($openFights as $entry)
                @php($fight = $entry->fight)

                <div class="fight pos-fight">
                    <div class="fight-top">
                        <span class="fight-no">{{ $fight->event?->name }} · Fight {{ $fight->fight_no }}</span>
                        <span class="badge ok">betting open</span>
                    </div>

                    <div class="sides">
                        <div class="side red {{ $fight->isSideLocked(\App\Models\Fight::SIDE_RED) ? 'is-locked' : '' }}">
                            <div class="name">
                                MERON
                                @if ($fight->isSideLocked(\App\Models\Fight::SIDE_RED))
                                    <span class="badge warn">LOCKED</span>
                                @endif
                            </div>
                            <div class="odds {{ $fight->isCappedFor(\App\Models\Fight::SIDE_RED) ? 'is-capped' : '' }}">{{ number_format($fight->odds_red, 3) }}×</div>
                            <div class="odds-label">posted odds</div>
                            <div class="stake">{{ number_format($entry->red_stake) }} credits on meron</div>
                        </div>
                        <div class="vs">VS</div>
                        <div class="side blue {{ $fight->isSideLocked(\App\Models\Fight::SIDE_BLUE) ? 'is-locked' : '' }}">
                            <div class="name">
                                WALA
                                @if ($fight->isSideLocked(\App\Models\Fight::SIDE_BLUE))
                                    <span class="badge warn">LOCKED</span>
                                @endif
                            </div>
                            <div class="odds {{ $fight->isCappedFor(\App\Models\Fight::SIDE_BLUE) ? 'is-capped' : '' }}">{{ number_format($fight->odds_blue, 3) }}×</div>
                            <div class="odds-label">posted odds</div>
                            <div class="stake">{{ number_format($entry->blue_stake) }} credits on wala</div>
                        </div>
                    </div>

                    @if ($fight->isCappedFor(\App\Models\Fight::SIDE_RED) || $fight->isCappedFor(\App\Models\Fight::SIDE_BLUE))
                        <div class="cap-note">
                            <strong>Bet stopper {{ number_format($fight->stop_ratio, 3) }}× caps this fight.</strong>
                            @if ($fight->isCappedFor(\App\Models\Fight::SIDE_RED))
                                <span>Meron posted {{ number_format($fight->odds_red, 3) }}× &rarr; pays {{ number_format($fight->effectiveOddsFor(\App\Models\Fight::SIDE_RED), 3) }}×.</span>
                            @endif
                            @if ($fight->isCappedFor(\App\Models\Fight::SIDE_BLUE))
                                <span>Wala posted {{ number_format($fight->odds_blue, 3) }}× &rarr; pays {{ number_format($fight->effectiveOddsFor(\App\Models\Fight::SIDE_BLUE), 3) }}×.</span>
                            @endif
                        </div>
                    @endif

                    <div class="pos-draw">
                        <span><strong>DRAW</strong> ×{{ number_format($fight->drawRatio(), 3) }}</span>
                        <span class="muted small">{{ number_format($entry->draw_stake) }} credits on draw</span>
                    </div>

                    <form method="POST" action="{{ route('pos.bets.store') }}" class="pos-bet-form">
                        @csrf
                        <input type="hidden" name="fight_id" value="{{ $fight->id }}">

                        <label class="field" style="margin:12px 0 0">
                            <span>Bet amount</span>
                            <input class="input amount-input" type="number" name="amount" min="{{ config('pos.min_bet') }}"
                                   max="{{ config('pos.max_bet') }}" step="1" placeholder="0" required
                                   inputmode="numeric" @disabled(! $shift)>
                        </label>

                        <div class="row" style="justify-content:space-between;margin:0 0 6px">
                            <span class="muted small">Quick amount</span>
                            <span class="muted small">tap an amount, then pick a side</span>
                        </div>

                        <div class="amt-grid" data-amount-grid>
                            @foreach (array_merge(range(100, 900, 100), range(1000, 9000, 1000), [10000], range(20000, 100000, 10000)) as $amount)
                                <button type="button" class="amt" data-amount="{{ $amount }}">{{ number_format($amount) }}</button>
                            @endforeach
                        </div>

                        <div class="pick-grid three">
                            <button class="pick meron" name="side" value="red" @disabled(! $shift || $fight->isSideLocked(\App\Models\Fight::SIDE_RED))>
                                <span class="pick-side">MERON</span>
                                <span class="pick-meta">
                                    @if ($fight->isSideLocked(\App\Models\Fight::SIDE_RED))
                                        locked by pit
                                    @else
                                        PAYS {{ number_format($fight->effectiveOddsFor(\App\Models\Fight::SIDE_RED), 3) }}×{{ $fight->isCappedFor(\App\Models\Fight::SIDE_RED) ? ' · capped' : '' }}
                                    @endif
                                </span>
                            </button>

                            <button class="pick wala" name="side" value="blue" @disabled(! $shift || $fight->isSideLocked(\App\Models\Fight::SIDE_BLUE))>
                                <span class="pick-side">WALA</span>
                                <span class="pick-meta">
                                    @if ($fight->isSideLocked(\App\Models\Fight::SIDE_BLUE))
                                        locked by pit
                                    @else
                                        PAYS {{ number_format($fight->effectiveOddsFor(\App\Models\Fight::SIDE_BLUE), 3) }}×{{ $fight->isCappedFor(\App\Models\Fight::SIDE_BLUE) ? ' · capped' : '' }}
                                    @endif
                                </span>
                            </button>

                            <button class="pick draw" name="side" value="draw" @disabled(! $shift)>
                                <span class="pick-side">DRAW</span>
                                <span class="pick-meta">PAYS {{ number_format($fight->drawRatio(), 3) }}×</span>
                            </button>
                        </div>

                        <p class="pos-keys">
                            <span><kbd>TAB</kbd> amount</span>
                            <span><kbd>F2</kbd> meron</span>
                            <span><kbd>F3</kbd> wala</span>
                            <span><kbd>F4</kbd> draw</span>
                        </p>

                        @if (! $shift)
                            <p class="muted small" style="margin-top:8px">Open a shift to enable betting.</p>
                        @elseif ($fight->isSideLocked(\App\Models\Fight::SIDE_RED) || $fight->isSideLocked(\App\Models\Fight::SIDE_BLUE))
                            <p class="muted small" style="margin-top:8px">
                                Locked side(s) shown above are closed by the pit — take the open side only.
                            </p>
                        @endif
                    </form>
                </div>
            @empty
                <div class="empty">No fight is currently open for betting. Wait for the Game Master to open one.</div>
            @endforelse
