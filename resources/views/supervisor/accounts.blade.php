@extends('layouts.app')

@section('title', 'Accounts')
@section('heading', 'Accounts')
@section('subheading', 'Supervisor, Game Master and POS teller accounts. Suspending an account blocks sign-in immediately.')

@section('content')
    <div class="grid two">
        <div class="card">
            <div class="card-head"><h2>Create account</h2></div>

            <p class="muted small">
                POS tellers are onboarded here: pick <strong>POS Teller</strong>, give the teller code, assign their
                terminal, and set opening credits if needed. A till is not tied to one Game Master — it takes bets on
                whichever card is open and shows up on the screens of the Game Master on duty. A teller bound
                to a terminal is automatically clocked in when they sign in there and clocked out when they sign out.
                Everything that changes a POS account — creation, credits, password resets, suspensions — belongs to
                this screen; the Game Master keeps a read-only roster. Stuck tills are force-closed under
                <strong>POS shifts</strong>.
            </p>

            <form method="POST" action="{{ route('supervisor.accounts.store') }}">
                @csrf

                <div class="form-grid">
                    <label class="field"><span>Full name</span><input class="input" name="name" value="{{ old('name') }}" required></label>
                    <label class="field"><span>Username</span><input class="input" name="username" value="{{ old('username') }}" required></label>
                    <label class="field"><span>Password (min 8)</span><input class="input" type="password" name="password" required></label>
                    <label class="field">
                        <span>Role</span>
                        <select class="input" name="role" data-role-select required>
                            <option value="teller">POS Teller</option>
                            <option value="game_master">Game Master</option>
                            <option value="supervisor">Supervisor</option>
                        </select>
                    </label>
                </div>

                <div class="form-grid">
                    <label class="field" data-for-role="game_master"><span>Employee no.</span><input class="input" name="employee_no" value="{{ old('employee_no') }}"></label>

                    <label class="field" data-for-role="teller"><span>Teller code</span><input class="input" name="teller_code" placeholder="POS 005" value="{{ old('teller_code') }}"></label>

                    <label class="field" data-for-role="teller">
                        <span>Terminal (auto attendance)</span>
                        <select class="input" name="workstation_id">
                            <option value="">— none —</option>
                            @foreach ($workstations as $workstation)
                                <option value="{{ $workstation->id }}" @selected(old('workstation_id') == $workstation->id)>
                                    {{ $workstation->code }}{{ $workstation->isBound() ? '' : ' (unbound)' }}
                                    {{ $workstation->assignedTeller ? '— '.$workstation->assignedTeller->teller_code : '' }}
                                </option>
                            @endforeach
                        </select>
                    </label>

                    <label class="field"><span>Initial credits (optional, max {{ number_format(config('pos.max_top_up')) }})</span><input class="input" type="number" min="0" step="1" max="{{ config('pos.max_top_up') }}" name="initial_credits" value="{{ old('initial_credits') }}"></label>
                </div>

                <button class="btn btn-primary">Create account</button>
            </form>
        </div>

        <div class="card">
            <div class="card-head"><h2>Grant virtual credits</h2></div>
            <p class="muted small">
                Credits are recorded in the ledger with your account as the actor. There is no cash-in or
                cash-out path in this system — credits are play money for the simulation.
            </p>

            <p class="muted small">Use the <strong>Grant</strong> button on the account row below.</p>
        </div>
    </div>

    <div class="card" style="margin-top:14px">
        <div class="card-head">
            <h2>All accounts</h2>
            <form method="GET" class="inline-form">
                <select class="input" name="role">
                    <option value="">All roles</option>
                    <option value="supervisor" @selected($role === 'supervisor')>Supervisor</option>
                    <option value="game_master" @selected($role === 'game_master')>Game Master</option>
                    <option value="teller" @selected($role === 'teller')>Teller</option>
                </select>
                <button class="btn btn-sm">Filter</button>
            </form>
        </div>

        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr><th>Name</th><th>Username</th><th>Role</th><th>Assignment</th><th class="num">Balance</th><th>Status</th><th>Actions</th></tr>
                </thead>
                <tbody>
                @foreach ($users as $user)
                    <tr>
                        <td><strong>{{ $user->name }}</strong></td>
                        <td class="mono">{{ $user->username }}</td>
                        <td><span class="badge info">{{ $user->roleLabel() }}</span></td>
                        <td class="muted small">
                            @if ($user->tellerProfile)
                                {{ $user->tellerProfile->teller_code }}
                                @if ($user->tellerProfile->workstation)
                                    <div class="muted small">Terminal {{ $user->tellerProfile->workstation->code }}</div>
                                @endif
                            @elseif ($user->gameMasterProfile)
                                GM profile
                            @else
                                —
                            @endif
                        </td>
                        <td class="num">{{ number_format($user->creditBalance()) }}</td>
                        <td>
                            <span class="badge {{ $user->status === 'active' ? 'ok' : 'err' }}">{{ $user->status }}</span>
                        </td>
                        <td>
                            <div class="row" style="gap:6px">
                                @if ($user->isTeller())
                                    <a class="btn btn-sm btn-ghost" href="{{ route('supervisor.accounts.topups', $user) }}">Top-ups</a>
                                @endif

                                @if (! $user->isSupervisor())
                                    <form method="POST" action="{{ route('supervisor.accounts.credits', $user) }}" class="inline-form">
                                        @csrf
                                        <input class="input" type="number" name="amount" min="1" step="1" max="{{ config('pos.max_top_up') }}" placeholder="max {{ number_format(config('pos.max_top_up')) }}" style="width:110px" required>
                                        <button class="btn btn-sm btn-green">Grant</button>
                                    </form>
                                @endif

                                @if (! $user->isSupervisor())
                                    <form method="POST" action="{{ route('supervisor.accounts.password', $user) }}" class="inline-form" data-confirm="Reset the password for {{ $user->username }}?">
                                        @csrf
                                        <input class="input" type="text" name="password" placeholder="new password" style="width:130px" required>
                                        <button class="btn btn-sm">Reset</button>
                                    </form>
                                @endif

                                @if ($user->id !== auth()->id())
                                    <form method="POST" action="{{ route('supervisor.accounts.destroy', $user) }}" class="inline-form" data-confirm="Delete {{ $user->username }} permanently? Their shifts, slips and credits are removed with the account. This cannot be undone.">
                                        @csrf
                                        @method('DELETE')
                                        <button class="btn btn-sm btn-red">Delete</button>
                                    </form>
                                @endif

                                @if ($user->id !== auth()->id())
                                    <form method="POST" action="{{ route('supervisor.accounts.status', $user) }}" class="inline-form" data-confirm="Change status for {{ $user->username }}?">
                                        @csrf
                                        @method('PATCH')
                                        <select class="input" name="status" style="width:118px">
                                            <option value="active" @selected($user->status === 'active')>active</option>
                                            <option value="suspended" @selected($user->status === 'suspended')>suspended</option>
                                            <option value="archived" @selected($user->status === 'archived')>archived</option>
                                        </select>
                                        <button class="btn btn-sm">Apply</button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>

        <div class="pagination">{{ $users->links() }}</div>
    </div>
@endsection
