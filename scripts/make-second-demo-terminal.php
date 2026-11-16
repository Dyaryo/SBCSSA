<?php

/**
 * Second demo terminal: a teller account and profile for a second POS browser.
 *
 * A teller belongs to exactly one workstation, so running a till in a second
 * browser needs its own teller rather than re-pointing POS DEMO and taking the
 * first terminal offline. Idempotent: re-running resets the password, role and
 * terminal link and leaves existing credits alone.
 *
 *   C:\xampp\php\php.exe scripts\make-second-demo-terminal.php
 *
 * After running, bind the browser once (POS monitor -> Bind this terminal,
 * workstation POS-DEMO-2); strict device binding then lets this teller in.
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

$spec = [
    'username' => 'demo.teller2',
    'name' => 'Demo Teller 2',
    'password' => 'teller2026',
    'code' => 'POS DEMO 2',
    'workstation' => 'POS-DEMO-2',
];


$user = DB::transaction(function () use ($spec, $actor) {
    $user = User::where('username', $spec['username'])->first();

    if ($user) {
        $user->name = $spec['name'];
        $user->password = $spec['password'];
        $user->role = 'teller';
        $user->status = User::STATUS_ACTIVE;
        $user->save();
    } else {
        $user = User::create([
            'name' => $spec['name'],
            'username' => $spec['username'],
            'password' => $spec['password'],
            'role' => 'teller',
            'status' => User::STATUS_ACTIVE,
            'created_by' => $actor?->id,
        ]);
    }

    $workstation = Workstation::firstOrCreate(
        ['code' => $spec['workstation']],
        ['label' => 'Demo terminal 2 (second browser)']
    );

    $teller = TellerProfile::where('user_id', $user->id)->first();

    if (! $teller) {
        $teller = TellerProfile::create([
            'user_id' => $user->id,
            'teller_code' => $spec['code'],
            'game_master_id' => null,
            'workstation_id' => $workstation->id,
            'status' => 'active',
        ]);
    } else {
        $teller->teller_code = $spec['code'];
        $teller->game_master_id = null;
        $teller->workstation_id = $workstation->id;
        $teller->status = 'active';
        $teller->save();
    }

    $user->walletOrCreate();

    return $user;
});

$balance = (int) $user->wallet()->value('balance_credits');

if ($balance < 1000) {
    $wallets->seedCredits($user, 150000, $actor, 'Second demo till funding');
    $balance = (int) $user->wallet()->value('balance_credits');
}

$teller = TellerProfile::where('user_id', $user->id)->first();
$workstation = Workstation::where('code', $spec['workstation'])->first();

echo "username:     {$spec['username']}\n";
echo "password:     {$spec['password']}\n";
echo "teller code:  {$teller->teller_code} (no Game Master assignment)\n";
echo "workstation:  {$workstation->code} (id {$workstation->id})\n";
echo 'terminal:     '.($workstation->device_uuid ? 'bound to '.$workstation->device_uuid : 'NOT bound yet')."\n";
echo 'till balance: '.number_format($balance)." credits\n";
echo "\n";

if (! $workstation->device_uuid) {
    echo "Strict device binding is ON. Bind the browser once: sign in there as\n";
    echo "demo.gm, open POS monitor, choose {$workstation->code} and press\n";
    echo "'Bind this terminal'. Then {$spec['username']} can sign in on it.\n";
}
