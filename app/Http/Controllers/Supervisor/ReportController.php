<?php

namespace App\Http\Controllers\Supervisor;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Fight;
use App\Models\GameMasterProfile;
use App\Models\ReportSnapshot;
use App\Models\ShiftSession;
use App\Models\TellerProfile;
use App\Services\AuditService;
use App\Services\ReportService;
use Illuminate\Http\Request;
use Throwable;

class ReportController extends Controller
{
    public function __construct(
        private ReportService $reports,
        private AuditService $audit
    ) {
    }

    public function index(Request $request)
    {
        $scope = (string) $request->query('scope', 'system_monthly');
        $report = null;
        $error = null;

        if ($request->has('scope')) {
            try {
                $report = $this->reports->build($scope, $request->only([
                    'fight_id', 'event_id', 'shift_id', 'teller_id', 'game_master_id', 'date', 'month',
                ]));
            } catch (Throwable $e) {
                $error = $e->getMessage();
            }
        }

        $options = [
            'scopes' => ReportService::SCOPES,
            'fights' => Fight::with('event')->latest()->limit(50)->get(),
            'events' => Event::latest()->limit(50)->get(),
            'shifts' => ShiftSession::with('teller.user')->latest()->limit(50)->get(),
            'tellers' => TellerProfile::with('user')->get(),
            'gameMasters' => GameMasterProfile::with('user')->get(),
        ];

        return view('supervisor.reports.index', array_merge(compact('scope', 'report', 'error'), $options));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'scope' => ['required', 'string'],
            'fight_id' => ['nullable', 'integer'],
            'event_id' => ['nullable', 'integer'],
            'shift_id' => ['nullable', 'integer'],
            'teller_id' => ['nullable', 'integer'],
            'game_master_id' => ['nullable', 'integer'],
            'date' => ['nullable', 'date'],
            'month' => ['nullable', 'string', 'max:7'],
        ]);

        $report = $this->reports->build($data['scope'], $data);
        $snapshot = $this->reports->snapshot($report, $request->user());

        $this->audit->log('report.snapshot_created', $snapshot, $request->user(), [], [
            'scope' => $snapshot->scope,
            'hash' => $snapshot->payload_hash,
        ]);

        return redirect()
            ->route('supervisor.reports.snapshots.show', $snapshot)
            ->with('status', 'Report snapshot #'.$snapshot->id.' generated and hash-sealed.');
    }

    public function csv(Request $request)
    {
        $scope = (string) $request->query('scope', 'system_monthly');
        $report = $this->reports->build($scope, $request->only([
            'fight_id', 'event_id', 'shift_id', 'teller_id', 'game_master_id', 'date', 'month',
        ]));

        $filename = 'report-'.$scope.'-'.now()->format('Ymd-His').'.csv';

        return response($this->reports->toCsv($report), 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    public function snapshots()
    {
        $snapshots = ReportSnapshot::orderByDesc('id')->paginate(20);

        return view('supervisor.reports.snapshots', compact('snapshots'));
    }

    public function showSnapshot(ReportSnapshot $snapshot)
    {
        $chainOk = $snapshot->prev_hash === null
            ? true
            : ReportSnapshot::where('payload_hash', $snapshot->prev_hash)->exists();

        return view('supervisor.reports.show', compact('snapshot', 'chainOk'));
    }
}
