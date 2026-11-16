<?php

namespace App\Http\Controllers\Supervisor;

use App\Http\Controllers\Controller;
use App\Models\GameMasterProfile;
use App\Models\TellerProfile;
use App\Models\User;
use App\Models\WalletTransaction;
use App\Models\Workstation;
use App\Services\AuditService;
use App\Services\WalletService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class AccountController extends Controller
{
    public function __construct(
        private WalletService $wallets,
        private AuditService $audit
    ) {
    }

    public function index(Request $request)
    {
        $role = $request->query('role');

        $users = User::with(['wallet', 'gameMasterProfile', 'tellerProfile.gameMaster.user'])
            ->when($role, fn ($q) => $q->where('role', $role))
            ->orderBy('role')
            ->orderBy('name')
            ->paginate(25)
            ->withQueryString();

        $workstations = Workstation::with('assignedTeller.user')->orderBy('code')->get();

        return view('supervisor.accounts', compact('users', 'workstations', 'role'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'username' => ['required', 'string', 'max:50', 'unique:users,username'],
            'password' => ['required', 'string', 'min:8'],
            'role' => ['required', 'in:supervisor,game_master,teller'],
            'employee_no' => ['nullable', 'string', 'max:40'],
            'teller_code' => ['nullable', 'string', 'max:20', 'unique:teller_profiles,teller_code'],
            'workstation_id' => ['nullable', 'integer', 'exists:workstations,id'],
            'initial_credits' => ['nullable', 'integer', 'min:0', 'max:'.config('pos.max_top_up')],
        ]);

        if ($data['role'] === 'teller' && empty($data['teller_code'])) {
            return back()->withErrors(['teller_code' => 'A teller code (e.g. POS 001) is required.'])->withInput();
        }

        $user = DB::transaction(function () use ($data, $request) {
            $user = User::create([
                'name' => $data['name'],
                'username' => $data['username'],
                'password' => $data['password'],
                'role' => $data['role'],
                'status' => User::STATUS_ACTIVE,
                'created_by' => $request->user()->id,
            ]);

            if ($data['role'] === 'game_master') {
                GameMasterProfile::create([
                    'user_id' => $user->id,
                    'employee_no' => $data['employee_no'] ?? null,
                ]);
            }

            if ($data['role'] === 'teller') {
                $teller = TellerProfile::create([
                    'user_id' => $user->id,
                    'teller_code' => strtoupper($data['teller_code']),
                    // Tills are not tied to one Game Master: whoever is on duty
                    // runs the card, so a new teller starts unassigned.
                    'game_master_id' => null,
                    'status' => 'active',
                ]);

                // The terminal assignment is what makes attendance automatic:
                // signing in on that terminal opens the shift for them.
                if (! empty($data['workstation_id'])) {
                    $workstation = Workstation::find($data['workstation_id']);

                    if ($workstation) {
                        TellerProfile::where('workstation_id', $workstation->id)
                            ->where('id', '!=', $teller->id)
                            ->update(['workstation_id' => null]);

                        $teller->workstation_id = $workstation->id;
                        $teller->save();

                        $workstation->assigned_teller_id = $teller->id;
                        $workstation->save();
                    }
                }
            }

            $user->walletOrCreate();

            return $user;
        });

        if (! empty($data['initial_credits'])) {
            $this->wallets->seedCredits($user, (int) $data['initial_credits'], $request->user(), 'Initial virtual credit allocation');
        }

        $this->audit->log('account.created', $user, $request->user(), [], [
            'username' => $user->username,
            'role' => $user->role,
            'workstation_id' => $user->tellerProfile?->workstation_id,
        ]);

        return back()->with('status', 'Account '.$user->username.' created.');
    }

    public function updateStatus(Request $request, User $user)
    {
        $data = $request->validate([
            'status' => ['required', 'in:active,suspended,archived'],
        ]);

        if ($user->id === $request->user()->id) {
            return back()->with('error', 'You cannot change the status of your own account.');
        }

        $before = ['status' => $user->status];
        $user->status = $data['status'];
        $user->save();

        // Keep the POS profile in step: a suspended login must not keep an
        // active till that the Game Master still sees as available.
        if ($user->tellerProfile) {
            $user->tellerProfile->status = $data['status'] === 'active' ? 'active' : 'suspended';
            $user->tellerProfile->save();
        }

        $this->audit->log('account.status_changed', $user, $request->user(), $before, ['status' => $user->status]);

        return back()->with('status', 'Account '.$user->username.' is now '.$user->status.'.');
    }

    /**
     * Password resets for POS logins, moved here from the Game Master.
     */
    public function resetPassword(Request $request, User $user)
    {
        $data = $request->validate([
            'password' => ['required', 'string', 'min:8'],
        ]);

        if ($user->isSupervisor() && $user->id !== $request->user()->id) {
            return back()->with('error', 'Another Supervisor password can only be reset by that person.');
        }

        $user->password = $data['password'];
        $user->save();

        $this->audit->log('account.password_reset', $user, $request->user(), [], ['username' => $user->username]);

        return back()->with('status', 'Password reset for '.$user->username.'.');
    }

    public function topUps(User $user)
    {
        abort_unless($user->isTeller(), 404);

        $user->loadMissing(['tellerProfile.gameMaster', 'wallet']);

        $base = WalletTransaction::with('performedBy')
            ->where('user_id', $user->id)
            ->where('type', WalletTransaction::TYPE_SEED);

        $total = (int) (clone $base)->sum('amount_credits');
        $count = (int) (clone $base)->count();
        $latest = (clone $base)->latest('created_at')->latest('id')->first();

        $topUps = (clone $base)
            ->latest('created_at')
            ->latest('id')
            ->paginate(50)
            ->withQueryString();

        return view('supervisor.topups', compact('user', 'topUps', 'total', 'count', 'latest'));
    }

    public function grantCredits(Request $request, User $user)
    {
        $data = $request->validate([
            'amount' => ['required', 'integer', 'min:1', 'max:'.config('pos.max_top_up')],
            'reason' => ['nullable', 'string', 'max:200'],
        ]);

        if ($user->isSupervisor()) {
            return back()->with('error', 'Supervisor accounts do not hold playable credits.');
        }

        $transaction = $this->wallets->seedCredits(
            $user,
            (int) $data['amount'],
            $request->user(),
            $data['reason'] ?? 'Supervisor allocation'
        );

        $this->audit->log('wallet.seeded', $user, $request->user(), [], [
            'amount' => (int) $data['amount'],
            'balance_after' => (int) $transaction->balance_after,
        ]);

        return back()->with('status', number_format($data['amount']).' credits granted to '.$user->username.'.');
    }

    /**
     * Remove an account outright, for staff who left or a terminal that changed
     * hands. A teller's own shifts and bets go with the account, because the
     * database refuses to orphan them; a Game Master who still owns events is
     * refused instead, since deleting a card would destroy other people's money
     * records. The deletion is written to the audit log before the row goes.
     */
    public function destroy(Request $request, User $user)
    {
        if ($user->id === $request->user()->id) {
            return back()->with('error', 'You cannot delete the account you are signed in as.');
        }

        if ($user->isSupervisor()) {
            $others = User::where('role', 'supervisor')->where('id', '!=', $user->id)->count();

            if ($others === 0) {
                return back()->with('error', 'That is the last Supervisor account and cannot be deleted.');
            }
        }

        $before = [
            'username' => $user->username,
            'name' => $user->name,
            'role' => $user->role,
            'status' => $user->status,
            'teller_code' => $user->tellerProfile?->teller_code,
        ];

        try {
            DB::transaction(function () use ($user, $request, $before) {
                // Free the terminal so it can be bound to a new account; the
                // profile and wallet themselves go with the user (cascade).
                if ($teller = $user->tellerProfile) {
                    Workstation::where('assigned_teller_id', $teller->id)
                        ->update(['assigned_teller_id' => null]);

                    // The teller's own book: their slips and shift rows cannot
                    // survive the profile (RESTRICT), and they are this account's
                    // records alone. Their wallet and ledger rows follow the user
                    // (cascade), so no orphaned credits are left behind.
                    $teller->bets()->delete();
                    $teller->shifts()->delete();
                }

                // Written before the row disappears: audit_logs holds no foreign
                // key to users, so the record survives the deletion.
                $this->audit->log('account.deleted', $user, $request->user(), $before, []);

                $user->delete();
            });
        } catch (\Illuminate\Database\QueryException $e) {
            return back()->with('error', 'Cannot delete '.$before['username'].': the account still owns fights, bets or shifts. Reset the floor or archive the account instead.');
        }

        return back()->with('status', 'Account '.$before['username'].' deleted.');
    }
}
