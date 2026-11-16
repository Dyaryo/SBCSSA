<div class="card" style="margin-bottom:14px">
    <div class="card-head">
        <h2>POS tills running low</h2>
        <span class="muted small">below {{ number_format($lowTillThreshold) }} credits</span>
    </div>

    @if ($lowTills->isEmpty())
        <p class="muted small" style="margin:0">
            Every active till is above {{ number_format($lowTillThreshold) }} credits — nothing to top up before the next event.
        </p>
    @else
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr><th>Teller</th><th>Game Master</th><th>Workstation</th><th class="num">Balance</th><th class="num">Shortfall</th><th></th></tr>
                </thead>
                <tbody>
                @foreach ($lowTills as $row)
                    @php($teller = $row['teller'])
                    @php($topUp = min((int) config('pos.max_top_up'), max(1, $lowTillThreshold - $row['balance'])))
                    <tr class="win-row" data-low-till="{{ $teller->teller_code }}">
                        <td>
                            <strong>{{ $teller->teller_code }}</strong>
                            <div class="muted small">{{ $teller->displayName() }}</div>
                        </td>
                        <td class="muted small">{{ $teller->gameMaster?->displayName() ?? '—' }}</td>
                        <td class="muted small">{{ $teller->workstation?->code ?? 'unbound' }}</td>
                        <td class="num"><span class="payout-now">{{ number_format($row['balance']) }}</span></td>
                        <td class="num">{{ number_format(max(0, $lowTillThreshold - $row['balance'])) }}</td>
                        <td>
                            <form method="POST" action="{{ route('supervisor.accounts.credits', $teller->user_id) }}"
                                  class="inline-form"
                                  data-confirm="Top up {{ $teller->teller_code }} by {{ number_format($topUp) }} credits?">
                                @csrf
                                <input type="hidden" name="reason" value="Low-till top-up from dashboard">
                                <input class="input" type="number" name="amount" min="1" step="1" max="{{ config('pos.max_top_up') }}"
                                       value="{{ $topUp }}" style="width:110px" required
                                       aria-label="Credits to grant to {{ $teller->teller_code }}">
                                <button class="btn btn-sm btn-green">Top up</button>
                            </form>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>

        <p class="muted small" style="margin:10px 0 0">
            <strong>Top up</strong> grants the amount shown (the exact shortfall, up to
            {{ number_format(config('pos.max_top_up')) }} credits) straight from this card; change the figure for a
            different amount, or use <strong>Accounts → Grant</strong> to fund a till that is not flagged.
            Every allocation is appended to the ledger with your account as the actor, so the till history explains every
            balance. The warning line is set by <strong>POS_LOW_TILL</strong> in <code>.env</code> (default 1,000 credits).
        </p>
    @endif
</div>
