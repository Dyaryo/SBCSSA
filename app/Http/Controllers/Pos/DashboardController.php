<?php

namespace App\Http\Controllers\Pos;

use App\Exceptions\BettingException;
use App\Http\Controllers\Controller;
use App\Models\Bet;
use App\Models\Fight;
use App\Models\ShiftSession;
use App\Models\Workstation;
use App\Services\AuditService;
use App\Services\BettingService;
use App\Services\PayoutService;
use App\Services\ReportService;
use App\Services\ShiftSlipService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class DashboardController extends Controller
{
    public function __construct(
        private BettingService $betting,
        private AuditService $audit,
        private ReportService $reports,
        private ShiftSlipService $slips,
        private PayoutService $payouts
    ) {
    }

    public function index(Request $request)
    {
        $user = $request->user();
        $teller = $user->tellerProfile;
        abort_unless($teller, 403, 'This login is not linked to a POS profile.');

        $wallet = $user->walletOrCreate();
        $shift = $teller->openShift();
        $workstation = $teller->workstation ?: Workstation::find($request->session()->get('workstation_id'));

        $openFights = $this->openFightsFor($teller);

        $recent = Bet::with('fight')
            ->where('teller_id', $teller->id)
            ->latest('placed_at')
            ->limit(8)
            ->get();

        // The teller's second job: hand over the winnings on graded winning
        // slips, once each.
        $payoutsDue = Bet::with('fight')
            ->where('teller_id', $teller->id)
            ->where('status', Bet::STATUS_WON)
            ->whereNull('paid_out_at')
            ->orderBy('settled_at')
            ->get()
            ->map(fn (Bet $bet) => [
                'bet' => $bet,
                'code' => $this->slips->code('BET', $bet->id, (int) $bet->amount_credits, (string) $bet->placed_at),
            ]);

        $windowSignature = $this->windowSignature($openFights, $shift?->id);
        $fightSignature = $this->fightSignature($openFights);

        return view('pos.dashboard', compact(
            'teller', 'wallet', 'shift', 'workstation', 'openFights', 'recent', 'payoutsDue', 'windowSignature', 'fightSignature'
        ));
    }

    /**
     * Cheap poll target for the terminal: when the Game Master opens or closes
     * a fight this signature changes and the window refreshes itself instead
     * of the teller reloading by hand.
     */
    public function fightFeed(Request $request)
    {
        $teller = $request->user()->tellerProfile;
        abort_unless($teller, 403);

        $openFights = $this->openFightsFor($teller);

        $shift = $teller->openShift();

        return response()
            ->json([
                'signature' => $this->windowSignature($openFights, $shift?->id),
                'fight_signature' => $this->fightSignature($openFights),
                'shift_id' => $shift?->id,
                'fights' => $openFights->count(),
            ])
            ->header('Cache-Control', 'no-store');
    }

    /**
     * The open cards only, rendered for an in-place swap. The terminal slides
     * the old cards out and the new fight in instead of reloading the page.
     */
    public function fightWindow(Request $request)
    {
        $teller = $request->user()->tellerProfile;
        abort_unless($teller, 403);

        $openFights = $this->openFightsFor($teller);
        $shift = $teller->openShift();

        return response()
            ->json([
                'signature' => $this->windowSignature($openFights, $shift?->id),
                'fight_signature' => $this->fightSignature($openFights),
                'shift_id' => $shift?->id,
                'html' => view('pos._open_fights', compact('openFights', 'shift'))->render(),
            ])
            ->header('Cache-Control', 'no-store');
    }

    /**
     * The fights this window may take money on right now.
     */
    private function openFightsFor(\App\Models\TellerProfile $teller)
    {
        // A window sells whatever card is open in the pit. A teller assigned to a
        // Game Master only sees that pit's card; an unassigned till (the normal
        // case now that tills are not tied to a GM) takes bets on any open card,
        // so the desk works whoever is on duty.
        $fights = Fight::with(['event', 'redCock', 'blueCock'])
            ->where('status', Fight::STATUS_BETTING_OPEN)
            ->whereHas('event', function ($q) use ($teller) {
                $q->where('status', '!=', 'cancelled');

                if ($teller->game_master_id) {
                    $q->where('game_master_id', $teller->game_master_id);
                }
            })
            ->orderBy('fight_no')
            ->get();

        return $fights->map(function (Fight $fight) {
            return (object) [
                'fight' => $fight,
                'red_stake' => $fight->stakedCredits(Fight::SIDE_RED),
                'blue_stake' => $fight->stakedCredits(Fight::SIDE_BLUE),
                'draw_stake' => $fight->stakedCredits(Fight::SIDE_DRAW),
            ];
        });
    }

    private function fightSignature($openFights): string
    {
        $rows = $openFights->map(fn ($entry) => implode(':', [
            $entry->fight->id,
            $entry->fight->status,
            (int) $entry->fight->red_locked,
            (int) $entry->fight->blue_locked,
            $entry->fight->odds_red,
            $entry->fight->odds_blue,
            $entry->fight->stop_ratio,
            $entry->red_stake,
            $entry->blue_stake,
            $entry->draw_stake,
        ]))->implode('|');

        return md5($rows);
    }

    private function windowSignature($openFights, ?int $shiftId): string
    {
        $rows = $openFights->map(fn ($entry) => implode(':', [
            $entry->fight->id,
            $entry->fight->status,
            (int) $entry->fight->red_locked,
            (int) $entry->fight->blue_locked,
            $entry->fight->odds_red,
            $entry->fight->odds_blue,
            $entry->fight->stop_ratio,
            $entry->red_stake,
            $entry->blue_stake,
            $entry->draw_stake,
        ]))->implode('|');

        return md5($rows.'#'.($shiftId ?? 0));
    }

    public function openShift(Request $request)
    {
        $teller = $request->user()->tellerProfile;
        abort_unless($teller, 403);

        if ($teller->openShift()) {
            return back()->with('error', 'A shift is already open on this account.');
        }

        $data = $request->validate([
            'opening_note' => ['nullable', 'string', 'max:200'],
        ]);

        $workstationId = $request->session()->get('workstation_id');

        $shift = ShiftSession::create([
            'teller_id' => $teller->id,
            'workstation_id' => $workstationId,
            'opened_at' => now(),
            'opening_note' => $data['opening_note'] ?? null,
            'status' => ShiftSession::STATUS_OPEN,
        ]);

        $this->audit->log('shift.opened', $shift, $request->user(), [], ['workstation_id' => $workstationId]);

        return back()->with('status', 'Shift #'.$shift->id.' opened. You can accept bets.');
    }

    public function closeShift(Request $request)
    {
        $teller = $request->user()->tellerProfile;
        abort_unless($teller, 403);

        $shift = $teller->openShift();

        if (! $shift) {
            return back()->with('error', 'There is no open shift to close.');
        }

        if ($shift->bets()->where('status', Bet::STATUS_ACTIVE)->exists()) {
            return back()->with('error', 'Unsettled bets remain on this shift. Ask the Game Master to settle or void those fights first.');
        }

        $data = $request->validate([
            'closing_note' => ['nullable', 'string', 'max:200'],
        ]);

        $shift->load('bets');

        $shift->total_bets_count = $shift->bets->count();
        $shift->total_amount_credits = (int) $shift->bets->sum('amount_credits');
        $shift->status = ShiftSession::STATUS_CLOSED;
        $shift->closed_at = now();
        $shift->closing_note = $data['closing_note'] ?? null;
        $shift->save();

        $this->audit->log('shift.closed', $shift, $request->user(), [], [
            'bets' => $shift->total_bets_count,
            'stake_total' => $shift->total_amount_credits,
        ]);

        $snapshot = $this->reports->snapshot(
            $this->reports->build('teller_shift', ['shift_id' => $shift->id]),
            $request->user()
        );

        return back()->with('status', 'Shift #'.$shift->id.' closed and sealed as snapshot #'.$snapshot->id.'.');
    }

    public function placeBet(Request $request)
    {
        $data = $request->validate([
            'fight_id' => ['required', 'integer', 'exists:fights,id'],
            'side' => ['required', 'in:red,blue,draw'],
            'amount' => ['required', 'integer', 'min:1', 'max:100000000'],
        ]);

        $fight = Fight::findOrFail($data['fight_id']);

        try {
            $bet = $this->betting->placeBet(
                $request->user(),
                $fight,
                $data['side'],
                (int) $data['amount'],
                null
            );
        } catch (BettingException $e) {
            return back()->with('error', $e->getMessage())->withInput();
        }

        return back()
            ->with('status', 'Bet #'.$bet->id.' recorded: '.strtoupper($bet->side).' for '.number_format($bet->amount_credits).' credits.')
            // The terminal prints the slip itself as soon as the bet is taken.
            ->with('print_receipt', route('pos.bets.receipt', $bet))
            // Big, satisfying confirmation on the terminal that the slip is in.
            ->with('bet_success', [
                'id' => $bet->id,
                'amount' => (int) $bet->amount_credits,
                'side' => strtoupper(Fight::sideLabel($bet->side)),
                'fight' => $fight->event?->name.' · Fight #'.$fight->fight_no,
            ]);
    }

    public function history(Request $request)
    {
        $teller = $request->user()->tellerProfile;
        abort_unless($teller, 403);

        $bets = Bet::with('fight')
            ->where('teller_id', $teller->id)
            ->when($request->query('status'), fn ($q, $status) => $q->where('status', $status))
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        $shifts = ShiftSession::where('teller_id', $teller->id)->orderByDesc('id')->limit(10)->get();

        return view('pos.history', compact('bets', 'shifts', 'teller'));
    }

    /**
     * Record that the teller handed the winnings over at the window.
     */
    public function recordPayout(Request $request, Bet $bet)
    {
        $teller = $request->user()->tellerProfile;
        abort_unless($teller, 403);
        abort_unless((int) $bet->teller_id === (int) $teller->id, 403, 'That slip belongs to another terminal.');

        try {
            $this->payouts->record($bet, $request->user(), $request->user());
        } catch (BettingException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()
            ->with(
                'status',
                'Payout of '.number_format($bet->potential_payout).' credits recorded for slip #'.$bet->id.'.'
            )
            // Print the claim slip automatically at the window.
            ->with('print_claim', route('pos.bets.claim', $bet));
    }

    /**
     * A teller may cancel their own live bet while the window is still open.
     * Once the pit locks the fight, only the Game Master can void it.
     */
    public function cancelForm(Request $request, Bet $bet)
    {
        $teller = $request->user()->tellerProfile;
        abort_unless($teller, 403);
        abort_unless((int) $bet->teller_id === (int) $teller->id, 403, 'That bet belongs to another teller.');

        if ($error = $this->tellerCancellationError($bet)) {
            return redirect()->route('pos.dashboard')->with('error', $error);
        }

        return view('pos.cancel', [
            'bet' => $bet->loadMissing(['fight.event', 'teller']),
            'teller' => $teller,
        ]);
    }

    public function cancelBet(Request $request, Bet $bet)
    {
        $teller = $request->user()->tellerProfile;
        abort_unless($teller, 403);
        abort_unless((int) $bet->teller_id === (int) $teller->id, 403, 'That bet belongs to another teller.');

        $data = $request->validate([
            'reason' => ['required', 'string', 'max:200'],
        ]);

        if ($error = $this->tellerCancellationError($bet)) {
            return back()->with('error', $error);
        }

        try {
            $this->betting->voidBet($bet, $data['reason'], $request->user(), true);
        } catch (BettingException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('pos.dashboard')
            ->with(
                'status',
                'Bet #'.$bet->id.' cancelled. '.number_format($bet->amount_credits).' credits returned to this terminal. The supervisor can see the cancellation.'
            )
            // The terminal prints the refund slip without another click.
            ->with('print_cancellation', route('pos.bets.cancellation', $bet));
    }

    public function cancellationSlip(Request $request, Bet $bet)
    {
        $teller = $request->user()->tellerProfile;
        abort_unless($teller, 403);
        abort_unless((int) $bet->teller_id === (int) $teller->id, 403, 'That bet belongs to another teller.');

        if ($bet->status !== Bet::STATUS_VOID) {
            return redirect()->route('pos.dashboard')->with('error', 'Only a cancelled bet has a cancellation slip.');
        }

        return view('pos.cancellation', [
            'bet' => $bet->loadMissing(['fight.event', 'teller', 'shift.workstation', 'voidedBy']),
            'slip' => $this->slips->forBet($bet),
        ]);
    }

    private function tellerCancellationError(Bet $bet): ?string
    {
        if ($bet->status !== Bet::STATUS_ACTIVE) {
            return 'Only an active bet can be cancelled.';
        }

        $bet->loadMissing('fight');

        if (! $bet->fight || $bet->fight->status !== Fight::STATUS_BETTING_OPEN) {
            return 'Betting is closed on this fight. Ask the Game Master to void the bet.';
        }

        return null;
    }

    /**
     * Scanned-slip lookup: the barcode/QR on a receipt carries the slip code,
     * so a scanner types it straight into this screen.
     */
    public function lookup(Request $request)
    {
        $teller = $request->user()->tellerProfile;
        abort_unless($teller, 403);

        $code = strtoupper(trim((string) $request->query('code', '')));
        $bet = null;
        $error = null;

        if ($code !== '') {
            if (! preg_match('/^BET-(\d{1,6})-([A-Z0-9]{8})$/', $code, $matches)) {
                $error = 'That is not a receipt code. It looks like BET-000123-ABCDEF12 and is printed under the barcode.';
            } else {
                $candidate = Bet::with(['fight.event', 'fight.redCock', 'fight.blueCock', 'teller.user'])
                    ->find((int) $matches[1]);

                if (! $candidate) {
                    $error = 'No bet matches that code on this server.';
                } else {
                    $expected = $this->slips->code(
                        'BET',
                        $candidate->id,
                        (int) $candidate->amount_credits,
                        (string) $candidate->placed_at
                    );

                    if (! hash_equals($expected, $code)) {
                        $error = 'That code does not match the record. The slip may be altered or from another system.';
                    } elseif ((int) $candidate->teller_id !== (int) $teller->id) {
                        $error = 'That slip was issued by '.($candidate->teller?->teller_code ?? 'another terminal').'. Payouts stay with the issuing terminal.';
                    } else {
                        $bet = $candidate;
                    }
                }
            }
        }

        return view('pos.lookup', compact('code', 'bet', 'error'));
    }

    /**
     * Printable receipt for one of this teller's own bets.
     */
    public function receipt(Request $request, Bet $bet)
    {
        $teller = $request->user()->tellerProfile;
        abort_unless($teller, 403);
        abort_unless((int) $bet->teller_id === (int) $teller->id, 403, 'That bet belongs to another teller.');

        return view('pos.receipt', [
            'slip' => $this->slips->forBet($bet),
            'auto' => $request->boolean('auto'),
        ]);
    }

    /**
     * Printable claim ticket for a winning slip: the payout record a player
     * keeps after the cashier hands the winnings over.
     */
    public function claim(Request $request, Bet $bet)
    {
        $teller = $request->user()->tellerProfile;
        abort_unless($teller, 403);
        abort_unless((int) $bet->teller_id === (int) $teller->id, 403, 'That bet belongs to another teller.');

        if ($bet->status !== Bet::STATUS_WON) {
            return back()->with('error', 'Only a winning slip can be claimed.');
        }

        return view('pos.claim', [
            'bet' => $bet->loadMissing(['fight.event', 'teller.user', 'shift.workstation', 'paidOutBy']),
            'slip' => $this->slips->forBet($bet),
        ]);
    }

    /**
     * Printable closeout slip for one of this teller's own shifts.
     */
    public function closeout(Request $request, ShiftSession $shift)
    {
        $teller = $request->user()->tellerProfile;
        abort_unless($teller, 403);
        abort_unless((int) $shift->teller_id === (int) $teller->id, 403, 'That shift belongs to another teller.');

        return view('pos.closeout', ['slip' => $this->slips->forShift($shift)]);
    }
}
