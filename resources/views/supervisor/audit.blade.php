@extends('layouts.app')

@section('title', 'Audit log')
@section('heading', 'Audit log')
@section('subheading', 'Append-only record of sign-ins, account changes, fight state changes, betting events and report sealing.')

@section('content')
    <div class="card">
        <div class="card-head">
            <h2>Entries</h2>

            <form method="GET" class="row" style="gap:6px">
                <input class="input" name="action" placeholder="action contains…" value="{{ request('action') }}" style="width:170px">
                <select class="input" name="role" style="width:140px">
                    <option value="">All roles</option>
                    <option value="supervisor" @selected(request('role') === 'supervisor')>Supervisor</option>
                    <option value="game_master" @selected(request('role') === 'game_master')>Game Master</option>
                    <option value="teller" @selected(request('role') === 'teller')>Teller</option>
                </select>
                <input class="input" type="date" name="from" value="{{ request('from') }}" style="width:150px">
                <input class="input" type="date" name="to" value="{{ request('to') }}" style="width:150px">
                <button class="btn btn-sm">Filter</button>
            </form>
        </div>

        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr><th>#</th><th>When</th><th>Actor</th><th>Action</th><th>Entity</th><th>IP</th><th>Detail</th></tr>
                </thead>
                <tbody>
                @forelse ($logs as $log)
                    <tr>
                        <td class="mono">{{ $log->id }}</td>
                        <td class="muted small">{{ $log->created_at?->format('d M Y H:i:s') }}</td>
                        <td>
                            <strong>{{ $log->actor?->username ?? 'system' }}</strong>
                            <div class="muted small">{{ $log->actor_role }}</div>
                        </td>
                        <td><span class="badge muted">{{ $log->action }}</span></td>
                        <td class="muted small">{{ $log->entity_type }}{{ $log->entity_id ? ' #'.$log->entity_id : '' }}</td>
                        <td class="mono small">{{ $log->ip_address }}</td>
                        <td>
                            @if ($log->before || $log->after)
                                <details>
                                    <summary>view</summary>
                                    <pre class="json">{{ json_encode(['before' => $log->before, 'after' => $log->after], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre>
                                </details>
                            @else
                                —
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="muted">No audit entries match the filter.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>

        <div class="pagination">{{ $logs->links() }}</div>
    </div>
@endsection
