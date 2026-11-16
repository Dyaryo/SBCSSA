<?php

namespace App\Http\Controllers\Supervisor;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Workstation;
use App\Services\AuditService;
use App\Services\FloorResetService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class MaintenanceController extends Controller
{
    public function __construct(
        private FloorResetService $reset,
        private AuditService $audit
    ) {
    }

    public function index()
    {
        return view('supervisor.maintenance', [
            'counts' => $this->reset->counts(),
            'accounts' => User::count(),
            'workstations' => Workstation::count(),
            'till' => 0,
        ]);
    }

    public function reset(Request $request)
    {
        $data = $request->validate([
            'confirm' => ['required', 'string', Rule::in(['RESET'])],
            'till' => ['nullable', 'integer', 'min:0', 'max:100000000'],
        ], [
            'confirm.in' => 'Type RESET exactly to confirm the floor reset.',
            'confirm.required' => 'Type RESET to confirm the floor reset.',
            'till.integer' => 'The starting till must be a whole number of credits.',
        ]);

        $result = $this->reset->reset(
            (int) ($data['till'] ?? 0),
            $request->session()->getId()
        );

        // The reset clears the audit table, so the record of who ran it is
        // written immediately afterwards.
        $this->audit->log('floor.reset', null, $request->user(), [], [
            'cleared' => $result['counts'],
            'till' => $result['till'],
            'accounts_kept' => User::count(),
            'workstations_kept' => Workstation::count(),
        ]);

        return redirect()
            ->route('supervisor.maintenance')
            ->with('status', 'Floor reset. '.number_format($result['counts']['events']).' event(s), '
                .number_format($result['counts']['fights']).' fight(s) and '
                .number_format($result['counts']['bets']).' bet(s) cleared; accounts and workstations kept. '
                .'Every till now holds '.number_format($result['till']).' credits.');
    }
}
