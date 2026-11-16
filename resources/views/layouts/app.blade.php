<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex, nofollow">
    <title>@yield('title', 'E-Sabong Virtual Console')</title>
    <link rel="stylesheet" href="{{ asset_v('css/app.css') }}">
    <link rel="icon" href="{{ asset_v('favicon.ico') }}" sizes="any">
    <link rel="icon" href="{{ asset_v('icons/rooster-mascot.svg') }}" type="image/svg+xml">
</head>
<body class="@yield('body-class')">
<header class="topbar">
    <div class="brand">
        <span class="dot"></span>
        E-SABONG
        <span class="brand-sub">Virtual Console</span>
    </div>

    <div class="topbar-right">
        <span class="badge muted">{{ auth()->user()->roleLabel() }}</span>
        <span class="who">Signed in as <strong>{{ auth()->user()->name }}</strong></span>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="btn btn-ghost btn-sm">Sign out</button>
        </form>
    </div>
</header>

<div class="shell">
    <aside class="sidebar">
        @php($u = auth()->user())

        @if ($u->isSupervisor())
            <div class="side-group">
                <p class="side-label">Monitoring</p>
                <nav class="nav">
                    <a href="{{ route('supervisor.dashboard') }}" class="{{ request()->routeIs('supervisor.dashboard') ? 'active' : '' }}">Overview</a>
                    <a href="{{ route('supervisor.live') }}" class="{{ request()->routeIs('supervisor.live') ? 'active' : '' }}">Live floor</a>
                    <a href="{{ route('supervisor.mobile') }}" class="{{ request()->routeIs('supervisor.mobile') ? 'active' : '' }}">Mobile report</a>
                    <a href="{{ route('supervisor.reports') }}" class="{{ request()->routeIs('supervisor.reports*') ? 'active' : '' }}">Reports</a>
                    <a href="{{ route('supervisor.reconciliation') }}" class="{{ request()->routeIs('supervisor.reconciliation*') ? 'active' : '' }}">Reconciliation</a>
                    <a href="{{ route('supervisor.audit') }}" class="{{ request()->routeIs('supervisor.audit') ? 'active' : '' }}">Audit log</a>
                </nav>
            </div>
            <div class="side-group">
                <p class="side-label">Administration</p>
                <nav class="nav">
                    <a href="{{ route('supervisor.accounts') }}" class="{{ request()->routeIs('supervisor.accounts') ? 'active' : '' }}">Accounts</a>
                    <a href="{{ route('supervisor.workstations') }}" class="{{ request()->routeIs('supervisor.workstations') ? 'active' : '' }}">Workstations</a>
                    <a href="{{ route('supervisor.shifts') }}" class="{{ request()->routeIs('supervisor.shifts*') ? 'active' : '' }}">POS shifts</a>
                    <a href="{{ route('supervisor.maintenance') }}" class="{{ request()->routeIs('supervisor.maintenance*') ? 'active' : '' }}">Floor reset</a>
                </nav>
            </div>
        @endif

        @if ($u->isGameMaster())
            <div class="side-group">
                <p class="side-label">Fights</p>
                <nav class="nav">
                    <a href="{{ route('gm.dashboard') }}" class="{{ request()->routeIs('gm.dashboard') ? 'active' : '' }}">Fight control</a>
                    <a href="{{ route('gm.fights') }}" class="{{ request()->routeIs('gm.fights', 'gm.fights.show') ? 'active' : '' }}">Fights &amp; grading</a>
                    <a href="{{ route('board') }}" target="_blank">Display board ↗</a>
                </nav>
            </div>
            <div class="side-group">
                <p class="side-label">Cards</p>
                <nav class="nav">
                    <a href="{{ route('gm.events') }}" class="{{ request()->routeIs('gm.events') ? 'active' : '' }}">Events</a>
                </nav>
            </div>
            <div class="side-group">
                <p class="side-label">Reports</p>
                <nav class="nav">
                    <a href="{{ route('gm.bets') }}" class="{{ request()->routeIs('gm.bets') ? 'active' : '' }}">Bet history</a>
                    <a href="{{ route('gm.history') }}" class="{{ request()->routeIs('gm.history') ? 'active' : '' }}">Fight history</a>
                    <a href="{{ route('gm.report') }}" class="{{ request()->routeIs('gm.report') ? 'active' : '' }}">Overall summary report</a>
                </nav>
            </div>
            <div class="side-group">
                <p class="side-label">POS oversight</p>
                <nav class="nav">
                    <a href="{{ route('gm.tellers') }}" class="{{ request()->routeIs('gm.tellers') ? 'active' : '' }}">Teller roster</a>
                    <a href="{{ route('gm.monitor') }}" class="{{ request()->routeIs('gm.monitor') ? 'active' : '' }}">POS monitor</a>
                </nav>
            </div>
        @endif

        @if ($u->isTeller())
            <div class="side-group">
                <p class="side-label">Terminal</p>
                <nav class="nav">
                    <a href="{{ route('pos.dashboard') }}" class="{{ request()->routeIs('pos.dashboard') ? 'active' : '' }}">Pay in &amp; pay out</a>
                    <a href="{{ route('pos.lookup') }}" class="{{ request()->routeIs('pos.lookup') ? 'active' : '' }}">Pay out — slip lookup</a>
                    <a href="{{ route('pos.history') }}" class="{{ request()->routeIs('pos.history') ? 'active' : '' }}">My history</a>
                </nav>
            </div>
        @endif
    </aside>

    <main class="main">
        <div class="page-head">
            <h1>@yield('heading', 'Dashboard')</h1>
            <p class="muted small">@yield('subheading')</p>
        </div>

        @if (session('status'))
            <div class="flash ok">{{ session('status') }}</div>
        @endif

        @if (session('error'))
            <div class="flash err">{{ session('error') }}</div>
        @endif

        @if ($errors->any())
            <div class="flash err">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @yield('content')

        <footer class="foot">
            Simulation system — all amounts are <strong>virtual credits with no cash value</strong>.
            No deposits, payouts or payment gateways are connected.
            · Server time <span data-clock>{{ now()->format('H:i:s') }}</span>
        </footer>
    </main>
</div>

<script src="{{ asset_v('js/app.js') }}"></script>
</body>
</html>
