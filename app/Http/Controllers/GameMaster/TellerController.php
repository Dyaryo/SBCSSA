<?php

namespace App\Http\Controllers\GameMaster;

use App\Http\Controllers\Controller;
use App\Models\ShiftSession;
use App\Models\TellerProfile;
use App\Services\ShiftSlipService;
use Illuminate\Http\Request;

/**
 * The Game Master sees the POS roster but does not administer it: accounts,
 * credits, password resets, suspensions and shift closures belong to the
 * Supervisor. Only the read-only listing and a slip reprint live here.
 */
class TellerController extends Controller
{
    public function __construct(private ShiftSlipService $slips)
    {
    }

    public function index(Request $request)
    {
        $gm = $request->user()->gameMasterProfile;
        abort_unless($gm, 403);

        $tellers = TellerProfile::with(['user', 'workstation', 'shifts' => fn ($q) => $q->latest('id')->limit(1)])
            ->withCount('bets')
            ->withSum('bets as stake_total', 'amount_credits')
            ->visibleToGameMaster($gm->id)
            ->orderBy('teller_code')
            ->get();

        return view('gm.tellers', compact('gm', 'tellers'));
    }

    /**
     * Read-only reprint of a closeout slip for a teller in this team.
     */
    public function closeout(Request $request, ShiftSession $shift)
    {
        // Unassigned pit tills are visible to the Game Master on duty.
        abort_unless(in_array($shift->teller?->game_master_id, [null, $request->user()->gameMasterProfile?->id], true), 403);

        return view('pos.closeout', ['slip' => $this->slips->forShift($shift)]);
    }
}
