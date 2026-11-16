<?php

namespace App\Http\Controllers\GameMaster;

use App\Http\Controllers\Controller;
use App\Models\Bet;
use App\Models\ShiftSession;
use App\Models\TellerProfile;
use App\Models\Workstation;
use App\Services\AuditService;
use Illuminate\Http\Request;

class MonitorController extends Controller
{
    public function __construct(private AuditService $audit)
    {
    }

    public function index(Request $request)
    {
        $gm = $request->user()->gameMasterProfile;
        abort_unless($gm, 403);

        $workstations = Workstation::with(['assignedTeller.user'])->orderBy('code')->get();

        $openShifts = ShiftSession::with(['teller.user', 'workstation', 'bets'])
            ->whereHas('teller', fn ($q) => $q->visibleToGameMaster($gm->id))
            ->where('status', ShiftSession::STATUS_OPEN)
            ->latest('opened_at')
            ->get();

        $recentBets = Bet::with(['fight', 'teller.user'])
            ->whereHas('teller', fn ($q) => $q->visibleToGameMaster($gm->id))
            ->latest('placed_at')
            ->limit(15)
            ->get();

        $tellers = TellerProfile::with('user')
            ->where('game_master_id', $gm->id)
            ->orderBy('teller_code')
            ->get();

        return view('gm.monitor', compact('workstations', 'openShifts', 'recentBets', 'tellers'));
    }

    /**
     * Bind the browser that is currently being used to a workstation record.
     */
    public function bindDevice(Request $request)
    {
        $gm = $request->user()->gameMasterProfile;
        abort_unless($gm, 403);

        $data = $request->validate([
            'code' => ['required', 'string', 'max:20', 'exists:workstations,code'],
            'device_uuid' => ['required', 'string', 'max:64'],
            'assigned_teller_id' => ['nullable', 'integer', 'exists:teller_profiles,id'],
        ]);

        $workstation = Workstation::where('code', $data['code'])->firstOrFail();

        $before = ['device_uuid' => $workstation->device_uuid, 'approved_at' => optional($workstation->approved_at)->toDateTimeString()];

        $workstation->device_uuid = $data['device_uuid'];
        $workstation->approved_at = now();
        $workstation->last_seen_at = now();

        if (! empty($data['assigned_teller_id'])) {
            $teller = TellerProfile::find($data['assigned_teller_id']);

            if ($teller && $teller->game_master_id === $gm->id) {
                $workstation->assigned_teller_id = $teller->id;
                $teller->workstation_id = $workstation->id;
                $teller->save();
            }
        }

        $workstation->save();

        $this->audit->log('workstation.bound', $workstation, $request->user(), $before, [
            'device_uuid' => $workstation->device_uuid,
            'assigned_teller_id' => $workstation->assigned_teller_id,
        ]);

        return back()->with('status', 'This terminal is now bound to '.$workstation->code.'.');
    }
}
