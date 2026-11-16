<?php

/**
 * One-off: creates one demo login per role (supervisor, game master, POS teller).
 *
 * Run with:  C:\xampp\php\php.exe scripts\make-demo-accounts.php
 *
 * All credit movement goes through WalletService so wallet_transactions stays
 * balanced and `php artisan system:verify` continues to pass.
 */

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\GameMasterProfile;
use App\Models\TellerProfile;
use App\Models\User;
use App\Models\Workstation;
use App\Services\WalletService;
use Illuminate\Support\Facades\DB;

$wallets = $app->make(WalletService::class);
$actor = User::where('username', 'supervisor')->first();

$demos = [
    // Lowercase + digits only: no Shift characters, so these can be typed on
    // any venue keyboard (a '#' is Shift+3 and was a common sign-in failure).
    // The only Supervisor is the real `supervisor` account (id 1). A demo
    // Supervisor used to be created here; it was removed so the venue has a
    // single admin login and no second supervisor to explain away.
    ['username' => 'demo.gm',         'name' => 'Demo Game Master', 'password' => 'master2026', 'role' => 'game_master'],
    ['username' => 'demo.teller',     'name' => 'Demo Teller',     'password' => 'teller2026', 'role' => 'teller'],
];

$created = [];

foreach ($demos as $spec) {
    $user = DB::transaction(function () use ($spec, $actor) {
        $user = User::where('username', $spec['username'])->first();

        if ($user) {
            $user->name = $spec['name'];
            $user->password = $spec['password']; // hashed by the model cast
            $user->role = $spec['role'];
            $user->status = User::STATUS_ACTIVE;
            $user->save();
        } else {
            $user = User::create([
                'name' => $spec['name'],
                'username' => $spec['username'],
                'password' => $spec['password'],
                'role' => $spec['role'],
                'status' => User::STATUS_ACTIVE,
                'created_by' => $actor?->id,
            ]);
        }

        if ($spec['role'] === 'game_master' && ! $user->gameMasterProfile) {
            GameMasterProfile::create([
                'user_id' => $user->id,
                'employee_no' => 'GM-DEMO',
            ]);
        }

        $user->walletOrCreate();

        return $user;
    });

    $created[$spec['role']] = $user;
}

// The demo teller reports to the demo Game Master and needs a terminal.
// Read the profiles fresh: the relation was loaded (as null) before the
// profile row existed, so the cached copy is stale.
$gmProfile = GameMasterProfile::where('user_id', $created['game_master']->id)->firstOrFail();
$teller = TellerProfile::where('user_id', $created['teller']->id)->first();

if (! $teller) {
    $teller = TellerProfile::create([
        'user_id' => $created['teller']->id,
        'teller_code' => 'POS DEMO',
        // Tills are not tied to a Game Master any more: the desk works the
        // card that is open, whoever is running it.
        'game_master_id' => null,
        'status' => 'active',
    ]);
} else {
    $teller->game_master_id = null;
    $teller->status = 'active';
    $teller->save();
}

Workstation::firstOrCreate(
    ['code' => 'POS-DEMO'],
    ['label' => 'Demo terminal (bind from the POS browser)']
);

// Fund the demo till so betting works immediately (ledger row included).
$balance = (int) $created['teller']->wallet()->value('balance_credits');
if ($balance < 1000) {
    $wallets->seedCredits($created['teller'], 150000, $actor, 'Demo till funding');
    $balance = (int) $created['teller']->wallet()->value('balance_credits');
}

echo "username          role          password\n";
echo "----------------------------------------------------\n";
foreach ($demos as $spec) {
    printf("%-17s %-13s %s\n", $spec['username'], $spec['role'], $spec['password']);
}
echo "\n";
echo "teller code:  POS DEMO (no Game Master assignment)\n";
echo "teller till:  ".number_format($balance)." credits\n";
echo "workstation:  POS-DEMO (code only, no fingerprint yet)\n";
echo "\n";
echo "Strict device binding is ON. Before the demo teller can sign in,\n";
echo "sign in as demo.gm on the POS browser, open POS monitor and use\n";
echo "'Bind this terminal' to bind POS-DEMO to that browser.\n";
