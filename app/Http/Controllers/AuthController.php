<?php

namespace App\Http\Controllers;

use App\Models\LoginEvent;
use App\Models\User;
use App\Services\AttendanceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function __construct(private AttendanceService $attendance)
    {
    }

    public function showLogin(Request $request)
    {
        if ($request->user()) {
            return redirect()->route('home');
        }

        return view('auth.login');
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'username' => ['required', 'string', 'max:50'],
            'password' => ['required', 'string'],
            'device_uuid' => ['nullable', 'string', 'max:64'],
        ]);

        $username = Str::lower($data['username']);
        $throttleKey = 'login:'.$username.'|'.$request->ip();

        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            throw ValidationException::withMessages([
                'username' => 'Too many failed attempts. Try again in '.RateLimiter::availableIn($throttleKey).' seconds.',
            ]);
        }

        $user = User::whereRaw('lower(username) = ?', [$username])->first();

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            RateLimiter::hit($throttleKey, 300);
            $this->record($request, 'failed_login', $user?->id, $data['username']);

            throw ValidationException::withMessages([
                'username' => 'These credentials do not match our records.',
            ]);
        }

        if (! $user->isActive()) {
            $this->record($request, 'blocked_login', $user->id, $data['username']);

            throw ValidationException::withMessages([
                'username' => 'This account is suspended. Contact your supervisor.',
            ]);
        }

        $workstationId = $user->isTeller() ? $this->resolveWorkstation($request, $user, $data['device_uuid'] ?? null) : null;

        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();
        $request->session()->put('workstation_id', $workstationId);

        $user->forceFill(['last_login_at' => now()])->save();
        RateLimiter::clear($throttleKey);
        $this->record($request, 'login', $user->id, $data['username'], $workstationId);

        // A teller on their own terminal is at work the moment they sign in.
        $attendance = $user->isTeller() ? $this->attendance->clockIn($user, $workstationId) : null;

        $redirect = redirect()->intended($this->homeFor($user));

        if ($attendance) {
            $redirect->with('status', 'Clocked in on '.($attendance->workstation?->code ?? 'terminal')
                .' — shift #'.$attendance->id.' is open.');
        }

        return $redirect;
    }

    public function logout(Request $request)
    {
        $user = $request->user();

        if ($user) {
            // Signing out is clocking out, when the till has nothing pending.
            $this->attendance->clockOut($user);
            $this->record($request, 'logout', $user->id, $user->username);
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    /**
     * Teller sign-in is tied to an approved workstation. In strict mode a
     * mismatched terminal fingerprint is refused.
     */
    private function resolveWorkstation(Request $request, User $user, ?string $submittedUuid): ?int
    {
        $teller = $user->tellerProfile;

        if (! $teller || $teller->status !== 'active') {
            $this->record($request, 'blocked_login', $user->id, $user->username);

            throw ValidationException::withMessages([
                'username' => 'This POS account is not active.',
            ]);
        }

        $workstation = $teller->workstation;

        if (config('pos.strict_device_binding')) {
            if (! $workstation || ! $workstation->isApproved() || ! $workstation->isBound()) {
                $this->record($request, 'blocked_login', $user->id, $user->username);

                throw ValidationException::withMessages([
                    'username' => 'This terminal is not registered. Ask your Game Master to register it first.',
                ]);
            }

            if (! $submittedUuid || ! hash_equals($workstation->device_uuid, $submittedUuid)) {
                $this->record($request, 'blocked_login', $user->id, $user->username, $workstation->id);

                throw ValidationException::withMessages([
                    'username' => 'This terminal is not authorised for your POS account.',
                ]);
            }
        }

        if ($workstation) {
            if (! $workstation->isBound() && $submittedUuid) {
                $workstation->device_uuid = $submittedUuid;
            }

            $workstation->last_seen_at = now();
            $workstation->save();
        }

        return $workstation?->id;
    }

    private function homeFor(User $user): string
    {
        return match (true) {
            $user->isSupervisor() => route('supervisor.dashboard'),
            $user->isGameMaster() => route('gm.dashboard'),
            default => route('pos.dashboard'),
        };
    }

    private function record(Request $request, string $kind, ?int $userId, ?string $username, ?int $workstationId = null): void
    {
        LoginEvent::create([
            'user_id' => $userId,
            'username_attempt' => $username,
            'workstation_id' => $workstationId ?? $request->session()->get('workstation_id'),
            'kind' => $kind,
            'ip_address' => $request->ip(),
            'user_agent' => Str::limit((string) $request->userAgent(), 250),
            'created_at' => now(),
        ]);
    }
}
