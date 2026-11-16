@extends('layouts.app')

@section('title', 'Floor reset')
@section('heading', 'Floor reset')
@section('subheading', 'Clear the operational floor for a fresh event day. Accounts, passwords, rosters and workstation bindings are untouched.')

@section('content')
    <div class="grid two">
        <div class="card">
            <div class="card-head"><h2>What is on the floor now</h2></div>

            <div class="stats">
                <div class="stat"><div class="k">Events</div><div class="v">{{ number_format($counts['events']) }}</div></div>
                <div class="stat"><div class="k">Fights</div><div class="v">{{ number_format($counts['fights']) }}</div></div>
                <div class="stat"><div class="k">Bets</div><div class="v">{{ number_format($counts['bets']) }}</div></div>
                <div class="stat"><div class="k">Shifts</div><div class="v">{{ number_format($counts['shifts']) }}</div></div>
                <div class="stat"><div class="k">Ledger rows</div><div class="v">{{ number_format($counts['ledger']) }}</div></div>
                <div class="stat"><div class="k">Report snapshots</div><div class="v">{{ number_format($counts['snapshots']) }}</div></div>
            </div>

            <p class="muted small" style="margin:10px 0 0">
                Kept: <strong>{{ number_format($accounts) }}</strong> account(s) and
                <strong>{{ number_format($workstations) }}</strong> workstation(s). Every wallet is set to the starting
                till below.
            </p>
        </div>

        <div class="card">
            <div class="card-head"><h2>Reset the floor</h2></div>

            <div class="flash err" style="margin-bottom:12px">
                This cannot be undone. Export or seal any report you still need before continuing.
            </div>

            <p class="small" style="margin-top:0">
                Removes every event, fight, bet, shift, wallet movement, report snapshot, audit row and login history.
                Accounts stay signed in only where their session survives — everyone else is signed out.
            </p>

            <form method="POST" action="{{ route('supervisor.maintenance.reset') }}"
                  data-confirm="Reset the floor? All events, fights, bets, shifts and history will be deleted. Accounts are kept.">
                @csrf

                <div class="form-grid">
                    <label class="field">
                        <span>Starting credits in every till</span>
                        <input class="input" type="number" name="till" min="0" max="100000000" step="1"
                               value="{{ old('till', 0) }}">
                    </label>

                    <label class="field">
                        <span>Type RESET to confirm</span>
                        <input class="input mono" name="confirm" placeholder="RESET" autocomplete="off" required>
                    </label>
                </div>

                <p class="muted small">
                    Leave the till at 0 for an empty floor, or set a starting balance (for example 150000) that every
                    teller begins the day with.
                </p>

                <button class="btn btn-red">Reset floor</button>
            </form>
        </div>
    </div>

    <div class="card" style="margin-top:14px">
        <div class="card-head"><h2>Kept by a floor reset</h2></div>

        <div class="grid two">
            <ul class="muted small" style="margin:0;padding-left:18px">
                <li>Supervisor, Game Master and POS teller accounts</li>
                <li>Passwords, account status and teller codes</li>
                <li>Game Master and teller assignments</li>
            </ul>
            <ul class="muted small" style="margin:0;padding-left:18px">
                <li>Workstations, fingerprints and approvals</li>
                <li>Terminal-to-teller bindings</li>
                <li>The signed-in Supervisor's own session</li>
            </ul>
        </div>
    </div>
@endsection
