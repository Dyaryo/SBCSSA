@extends('layouts.app')

@section('title', 'Sealed reports')
@section('heading', 'Sealed report snapshots')
@section('subheading', 'Each snapshot stores its payload, a SHA-256 hash, and the previous snapshot hash. Re-running a report does not change a sealed one.')

@section('content')
    <div class="card">
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr><th>#</th><th>Scope</th><th>Reference</th><th>Period</th><th>Hash</th><th>Chain</th><th>Generated</th><th></th></tr>
                </thead>
                <tbody>
                @forelse ($snapshots as $snapshot)
                    <tr>
                        <td class="mono">{{ $snapshot->id }}</td>
                        <td><span class="badge info">{{ $snapshot->scope }}</span></td>
                        <td class="muted">{{ $snapshot->scope_ref_id ?? 'system' }}</td>
                        <td class="muted small">{{ $snapshot->period_start?->toDateString() }} → {{ $snapshot->period_end?->toDateString() }}</td>
                        <td class="mono small">{{ substr($snapshot->payload_hash, 0, 16) }}…</td>
                        <td class="mono small">{{ $snapshot->prev_hash ? substr($snapshot->prev_hash, 0, 12).'…' : 'genesis' }}</td>
                        <td class="muted small">{{ $snapshot->generated_at?->format('d M Y H:i') }}</td>
                        <td><a class="btn btn-sm" href="{{ route('supervisor.reports.snapshots.show', $snapshot) }}">Open</a></td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="muted">No snapshots sealed yet.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>

        <div class="pagination">{{ $snapshots->links() }}</div>
    </div>
@endsection
