@extends('layouts.app')

@section('title', 'Workstations')
@section('heading', 'POS workstations')
@section('subheading', 'Register a terminal, bind its browser fingerprint, then approve it. With strict binding enabled, tellers can only sign in from their assigned terminal.')

@section('content')
    <div class="grid two">
        <div class="card">
            <div class="card-head"><h2>Register workstation</h2></div>

            <form method="POST" action="{{ route('supervisor.workstations.store') }}">
                @csrf
                <div class="form-grid">
                    <label class="field"><span>Code</span><input class="input" name="code" placeholder="POS-005" required></label>
                    <label class="field"><span>Label</span><input class="input" name="label" placeholder="Counter 5 — Window B" required></label>
                </div>

                <button class="btn btn-primary">Register</button>
            </form>

            <p class="muted small" style="margin-top:12px">
                Strict device binding is currently
                <strong>{{ config('pos.strict_device_binding') ? 'ON' : 'OFF' }}</strong>.
                Set <code class="mono">POS_STRICT_DEVICE_BINDING=true</code> in <code class="mono">.env</code> for production.
            </p>
        </div>

        <div class="card">
            <div class="card-head"><h2>Binding checklist</h2></div>
            <ol class="muted small" style="padding-left:18px; margin:0">
                <li>Register the workstation here.</li>
                <li>On that physical terminal, open the system and sign in as the Game Master.</li>
                <li>Go to <strong>POS monitor → Bind this terminal</strong> and pick the workstation code.</li>
                <li>Assign the teller and press <strong>Approve</strong> in the table below.</li>
                <li>Enable strict binding in <code class="mono">.env</code> before going live.</li>
            </ol>
        </div>
    </div>

    <div class="card" style="margin-top:14px">
        <div class="card-head"><h2>Terminals</h2></div>

        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr><th>Code</th><th>Label</th><th>Assigned teller</th><th>Fingerprint</th><th>Approved</th><th>Last seen</th><th>Actions</th></tr>
                </thead>
                <tbody>
                @forelse ($workstations as $workstation)
                    <tr>
                        <td><strong>{{ $workstation->code }}</strong></td>
                        <td class="muted">{{ $workstation->label }}</td>
                        <td>{{ $workstation->assignedTeller?->teller_code ?? '—' }}</td>
                        <td class="mono small">{{ $workstation->isBound() ? substr($workstation->device_uuid, 0, 14).'…' : 'not bound' }}</td>
                        <td>
                            <span class="badge {{ $workstation->isApproved() ? 'ok' : 'warn' }}">
                                {{ $workstation->isApproved() ? 'approved' : 'pending' }}
                            </span>
                        </td>
                        <td class="muted small">{{ $workstation->last_seen_at?->format('d M H:i') ?? '—' }}</td>
                        <td>
                            <div class="row" style="gap:6px">
                                <form method="POST" action="{{ route('supervisor.workstations.update', $workstation) }}" class="inline-form">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="action" value="assign">
                                    <select class="input" name="assigned_teller_id" style="width:130px">
                                        <option value="">— teller —</option>
                                        @foreach ($tellers as $teller)
                                            <option value="{{ $teller->id }}" @selected($workstation->assigned_teller_id === $teller->id)>
                                                {{ $teller->teller_code }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <button class="btn btn-sm">Assign</button>
                                </form>

                                @if ($workstation->isApproved())
                                    <form method="POST" action="{{ route('supervisor.workstations.update', $workstation) }}" class="inline-form" data-confirm="Revoke and clear the fingerprint for {{ $workstation->code }}?">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="action" value="revoke">
                                        <button class="btn btn-sm btn-ghost">Revoke</button>
                                    </form>
                                @else
                                    <form method="POST" action="{{ route('supervisor.workstations.update', $workstation) }}" class="inline-form">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="action" value="approve">
                                        <button class="btn btn-sm btn-green">Approve</button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="muted">No workstations registered.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
