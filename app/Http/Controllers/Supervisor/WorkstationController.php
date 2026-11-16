<?php

namespace App\Http\Controllers\Supervisor;

use App\Http\Controllers\Controller;
use App\Models\TellerProfile;
use App\Models\Workstation;
use App\Services\AuditService;
use Illuminate\Http\Request;

class WorkstationController extends Controller
{
    public function __construct(private AuditService $audit)
    {
    }

    public function index()
    {
        $workstations = Workstation::with(['assignedTeller.user'])->orderBy('code')->get();
        $tellers = TellerProfile::with('user')->orderBy('teller_code')->get();

        return view('supervisor.workstations', compact('workstations', 'tellers'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:20', 'unique:workstations,code'],
            'label' => ['required', 'string', 'max:80'],
        ]);

        $workstation = Workstation::create([
            'code' => $data['code'],
            'label' => $data['label'],
        ]);

        $this->audit->log('workstation.created', $workstation, $request->user(), [], ['code' => $workstation->code]);

        return back()->with('status', 'Workstation '.$workstation->code.' registered. Bind it from the terminal itself.');
    }

    public function update(Request $request, Workstation $workstation)
    {
        $data = $request->validate([
            'action' => ['required', 'in:approve,revoke,assign'],
            'assigned_teller_id' => ['nullable', 'integer', 'exists:teller_profiles,id'],
        ]);

        $before = [
            'assigned_teller_id' => $workstation->assigned_teller_id,
            'device_uuid' => $workstation->device_uuid,
            'approved_at' => optional($workstation->approved_at)->toDateTimeString(),
        ];

        if ($data['action'] === 'approve') {
            if (! $workstation->isBound()) {
                return back()->with('error', 'This workstation has no device fingerprint yet. Sign in as Game Master on that terminal to bind it.');
            }

            $workstation->approved_at = now();
        }

        if ($data['action'] === 'revoke') {
            $workstation->approved_at = null;
            $workstation->device_uuid = null;
        }

        if ($data['action'] === 'assign') {
            $workstation->assigned_teller_id = $data['assigned_teller_id'] ?? null;

            if ($workstation->assigned_teller_id) {
                $teller = TellerProfile::find($workstation->assigned_teller_id);

                if ($teller) {
                    $teller->workstation_id = $workstation->id;
                    $teller->save();
                }
            }
        }

        $workstation->save();

        $this->audit->log('workstation.updated', $workstation, $request->user(), $before, [
            'assigned_teller_id' => $workstation->assigned_teller_id,
            'device_uuid' => $workstation->device_uuid,
            'approved_at' => optional($workstation->approved_at)->toDateTimeString(),
        ]);

        return back()->with('status', 'Workstation '.$workstation->code.' updated.');
    }
}
