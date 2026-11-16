<?php

namespace App\Http\Controllers\Supervisor;

use App\Http\Controllers\Controller;
use App\Services\ReconciliationService;
use Illuminate\Http\Request;

class ReconciliationController extends Controller
{
    public function __construct(private ReconciliationService $reconciliation)
    {
    }

    public function index(Request $request)
    {
        $date = (string) $request->query('date', now()->toDateString());

        try {
            $data = $this->reconciliation->build($date);
        } catch (\Throwable $e) {
            return redirect()
                ->route('supervisor.reconciliation')
                ->with('error', 'Could not read that date: '.$e->getMessage());
        }

        return view('supervisor.reconciliation', compact('data'));
    }

    public function csv(Request $request)
    {
        $date = (string) $request->query('date', now()->toDateString());
        $data = $this->reconciliation->build($date);

        return response($this->reconciliation->toCsv($data), 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="reconciliation-'.$data['date'].'.csv"',
        ]);
    }
}
