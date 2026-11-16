<?php

namespace App\Services;

use App\Models\Bet;
use App\Models\ShiftSession;
use App\Models\User;

/**
 * Attendance for a bound POS terminal: signing in opens the teller's shift and
 * signing out closes it. A teller who has no terminal assigned still opens and
 * closes their own shift from the POS screen.
 */
class AttendanceService
{
    public function __construct(
        private AuditService $audit,
        private ReportService $reports
    ) {
    }

    /**
     * Open a shift for the teller if the terminal is bound and no shift is
     * already running. Returns the newly opened shift, or null when nothing
     * had to be done.
     */
    public function clockIn(User $user, ?int $workstationId): ?ShiftSession
    {
        $teller = $user->tellerProfile;

        if (! $teller || ! $workstationId || $teller->openShift()) {
            return null;
        }

        $shift = ShiftSession::create([
            'teller_id' => $teller->id,
            'workstation_id' => $workstationId,
            'opened_at' => now(),
            'opening_note' => 'Attendance — clocked in at sign-in',
            'status' => ShiftSession::STATUS_OPEN,
        ]);

        $this->audit->log('shift.opened', $shift, $user, [], [
            'workstation_id' => $workstationId,
            'attendance' => true,
        ]);

        return $shift->load('workstation');
    }

    /**
     * Close the teller's shift when they sign out, unless slips are still
     * unsettled — those stay open for the Game Master or Supervisor to close.
     */
    public function clockOut(User $user): ?ShiftSession
    {
        $teller = $user->tellerProfile;

        if (! $teller) {
            return null;
        }

        $shift = $teller->openShift();

        if (! $shift) {
            return null;
        }

        if ($shift->bets()->where('status', Bet::STATUS_ACTIVE)->exists()) {
            return null;
        }

        $shift->load('bets');

        $shift->total_bets_count = $shift->bets->count();
        $shift->total_amount_credits = (int) $shift->bets->sum('amount_credits');
        $shift->status = ShiftSession::STATUS_CLOSED;
        $shift->closed_at = now();
        $shift->closing_note = 'Attendance — clocked out at sign-out';
        $shift->save();

        $this->audit->log('shift.closed', $shift, $user, [], [
            'bets' => $shift->total_bets_count,
            'stake_total' => $shift->total_amount_credits,
            'attendance' => true,
        ]);

        $this->reports->snapshot(
            $this->reports->build('teller_shift', ['shift_id' => $shift->id]),
            $user
        );

        return $shift;
    }
}
