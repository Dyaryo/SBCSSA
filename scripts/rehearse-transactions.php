<?php

/**
 * Rehearsal: drives one real credit round-trip through every money path the
 * console exposes, using the demo teller's own wallet and the demo event.
 *
 *   bet placed      -> bet_hold        (debit)
 *   bet cancelled   -> void_refund     (credit)
 *   fight settled   -> payout          (credit, stop-ratio capped)
 *   payout at window-> bet marked paid (no ledger entry; cash handover)
 *   result regraded -> adjustment      (reverses the earlier payout)
 *
 * Everything goes through BettingService / WalletService / PayoutService, the
 * same code the controllers call, so a pass here means the live paths work.
 * Run with:  C:\xampp\php\php.exe scripts\rehearse-transactions.php
 */

require __DIR__.'/../vendor/autoload.php';

$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Models\Bet;
use App\Models\Event;
use App\Models\Fight;
use App\Models\User;
use App\Models\WalletTransaction;
use App\Services\BettingService;
use App\Services\PayoutService;
use App\Services\WalletService;

$betting = $app->make(BettingService::class);
$wallets = $app->make(WalletService::class);
$payouts = $app->make(PayoutService::class);

$gm = User::where('username', 'demo.gm')->first();
$teller = User::where('username', 'demo.teller')->first();

if (! $gm || ! $teller) {
    fwrite(STDERR, "Demo accounts are missing. Run scripts\\make-demo-accounts.php first.\n");
    exit(1);
}

$profile = $teller->tellerProfile;
$shift = $profile?->openShift();

if (! $shift) {
    fwrite(STDERR, "Demo teller has no open shift; open one at /pos first.\n");
    exit(1);
}

$event = Event::find(2);

if (! $event) {
    fwrite(STDERR, "Demo event 2 is missing.\n");
    exit(1);
}

$balance = fn () => (int) $wallets->walletFor($teller)->fresh()->balance_credits;

$steps = [];
$failed = 0;

/**
 * Record one step: what we expected the wallet to do, and what it did.
 */
$check = function (string $label, int $before, int $expected, int $actual, callable $extra = null) use (&$steps, &$failed) {
    $ok = $actual === $before + $expected;
    $detail = '';

    if ($extra) {
        $result = $extra();
        $ok = $ok && $result[0];
        $detail = $result[1];
    }

    if (! $ok) {
        $failed++;
    }

    $steps[] = [
        'label' => $label,
        'before' => $before,
        'expected' => $expected,
        'after' => $actual,
        'verdict' => $ok ? 'PASS' : 'FAIL',
        'detail' => $detail,
    ];

    printf("  %-46s %9s -> %9s  (expect %+d)  %s %s\n",
        $label, number_format($before), number_format($actual), $expected,
        $ok ? 'PASS' : 'FAIL', $detail);
};

/** Ledger rows written for a bet, as a compact "type amount" list. */
$ledgerFor = function (Bet $bet, ?string $type = null) use ($teller) {
    $rows = WalletTransaction::where('user_id', $teller->id)
        ->where('bet_id', $bet->id)
        ->when($type, fn ($q) => $q->where('type', $type))
        ->get();

    return $rows->map(fn ($r) => $r->type.' '.number_format((int) $r->amount_credits))->implode(', ')
        ?: 'no rows tagged for this bet';
};

// Anything a previous, interrupted run left behind would block the shift from
// closing, so clear it out first: voiding refunds every live stake.
$leftovers = Fight::where('event_id', $event->id)
    ->where('fight_no', '>=', 2)
    ->get()
    ->reject(fn (Fight $f) => $f->isTerminal());

foreach ($leftovers as $stale) {
    $betting->voidFight($stale, 'Rehearsal cleanup of an earlier run', $gm);
    echo 'Cleaned up fight #'.$stale->fight_no." left by an earlier run (stakes refunded).\n";
}

echo "\n=== E-Sabong transaction rehearsal ===\n";
echo 'Event:  '.$event->name.' (#'.$event->id.")\n";
echo 'Teller: '.$teller->name.' ( shift #'.$shift->id." )\n";
printf("Opening balance: %s credits\n\n", number_format($balance()));

// ---------------------------------------------------------------- fight A ---
$fightNo = (int) Fight::where('event_id', $event->id)->max('fight_no') + 1;

$fightA = Fight::create([
    'event_id' => $event->id,
    'fight_no' => $fightNo,
    'odds_red' => 1.85,
    'odds_blue' => 2.05,
    'stop_ratio' => 1.40,
    'status' => Fight::STATUS_SCHEDULED,
    'scheduled_at' => now(),
]);

$betting->openBetting($fightA, $gm);
echo 'Fight A: #'.$fightA->fight_no.' (betting open, stop ratio 1.400)'."\n";

// 1. A bet debits the teller's terminal.
$before = $balance();
$betA = $betting->placeBet($teller, $fightA, Fight::SIDE_RED, 500, 'rehearsal-hold-'.uniqid());
$check('1. bet placed (500 meron) -> bet_hold', $before, -500, $balance(), function () use ($betA, $ledgerFor) {
    $rows = $ledgerFor($betA, WalletTransaction::TYPE_BET_HOLD);

    return [$betA->status === Bet::STATUS_ACTIVE && str_contains($rows, 'bet_hold'), '['.$rows.']'];
});

// 2. Cancelling it returns the stake.
$before = $balance();
$betting->voidBet($betA, 'Rehearsal: wrong side entered', $teller, true);
$check('2. bet cancelled -> void_refund', $before, +500, $balance(), function () use ($betA, $ledgerFor) {
    $rows = $ledgerFor($betA, WalletTransaction::TYPE_VOID_REFUND);
    $betA->refresh();

    return [$betA->status === Bet::STATUS_VOID && str_contains($rows, 'void_refund'), '['.$rows.']'];
});

// 3. A winning bet pays at the capped ratio (2.050 posted, capped to 1.400).
$before = $balance();
$betB = $betting->placeBet($teller, $fightA, Fight::SIDE_BLUE, 800, 'rehearsal-payout-'.uniqid());
$check('3. bet placed (800 wala) -> bet_hold', $before, -800, $balance(), function () use ($betB, $ledgerFor) {
    return [true, '['.$ledgerFor($betB, WalletTransaction::TYPE_BET_HOLD).']'];
});

$before = $balance();
// The pit closes the window before calling the result, exactly as the GM screen does.
$betting->lockBetting($fightA, $gm);
$betting->declareResult($fightA, Fight::RESULT_WALA, $gm);
$betB->refresh();
$expectedPayout = 1120; // 800 x stop ratio 1.400
$check('4. fight settled wala -> payout (capped 1.400)', $before, +$expectedPayout, $balance(), function () use ($betB, $ledgerFor, $expectedPayout) {
    $rows = $ledgerFor($betB, WalletTransaction::TYPE_PAYOUT);
    $ok = $betB->status === Bet::STATUS_WON && (int) $betB->potential_payout === $expectedPayout;

    return [$ok, '['.$rows.']'];
});

// 4. Handing the cash over at the window.
$before = $balance();
$payouts->record($betB, $teller, $teller);
$betB->refresh();
$check('5. payout recorded at window (cash handover)', $before, 0, $balance(), function () use ($betB) {
    return [$betB->status === Bet::STATUS_WON && $betB->paid_out_at !== null, '(bet marked paid, no ledger entry)'];
});

// ---------------------------------------------------------------- fight B ---
// 5. A regrade has to reverse the payout that was already credited.
$fightB = Fight::create([
    'event_id' => $event->id,
    'fight_no' => $fightNo + 1,
    'odds_red' => 1.85,
    'odds_blue' => 2.05,
    'stop_ratio' => 1.40,
    'status' => Fight::STATUS_SCHEDULED,
    'scheduled_at' => now(),
]);

$betting->openBetting($fightB, $gm);
echo "\n".'Fight B: #'.$fightB->fight_no.' (for the regrade path)'."\n";

$before = $balance();
$betC = $betting->placeBet($teller, $fightB, Fight::SIDE_RED, 300, 'rehearsal-regrade-'.uniqid());
$check('6. bet placed (300 meron) -> bet_hold', $before, -300, $balance(), function () use ($betC, $ledgerFor) {
    return [true, '['.$ledgerFor($betC, WalletTransaction::TYPE_BET_HOLD).']'];
});

$before = $balance();
$betting->lockBetting($fightB, $gm);
$betting->declareResult($fightB, Fight::RESULT_MERON, $gm);
$check('7. fight settled meron -> payout (capped 1.400)', $before, +420, $balance(), function () use ($betC, $ledgerFor) {
    return [true, '['.$ledgerFor($betC, WalletTransaction::TYPE_PAYOUT).']'];
});

$before = $balance();
$betting->regrade($fightB, Fight::RESULT_WALA, $gm, 'Rehearsal: result corrected');
$check('8. result regraded to wala -> adjustment', $before, -420, $balance(), function () use ($betC, $ledgerFor) {
    $rows = $ledgerFor($betC, WalletTransaction::TYPE_ADJUSTMENT);

    return [str_contains($rows, 'adjustment'), '['.$rows.']'];
});

// ------------------------------------------------------------------ ledger ---
echo "\n=== Ledger check ===\n";
$report = $wallets->verify();

foreach ($report as $key => $value) {
    if (is_array($value)) {
        continue;
    }
    printf("  %-28s %s\n", $key, is_bool($value) ? ($value ? 'OK' : 'FAILED') : $value);
}

$failing = array_filter($report, fn ($v) => $v === false || (is_array($v) && ! empty($v['errors'])));

echo "\n=== Transaction types exercised by this run ===\n";

foreach ([
    WalletTransaction::TYPE_SEED => 'credits loaded (supervisor top-up) — existing rows only',
    WalletTransaction::TYPE_BET_HOLD => 'bet placed',
    WalletTransaction::TYPE_VOID_REFUND => 'bet cancelled / fight voided',
    WalletTransaction::TYPE_PAYOUT => 'fight settled with a winner or draw',
    WalletTransaction::TYPE_ADJUSTMENT => 'result regraded',
    WalletTransaction::TYPE_SHIFT_CORRECTION => 'shift correction — no code path writes this',
] as $type => $meaning) {
    $count = WalletTransaction::where('type', $type)->count();
    printf("  %-18s %4d rows  %s\n", $type, $count, $meaning);
}

echo "\n";
printf("%d step(s) checked, %d failed. Closing balance: %s credits.\n",
    count($steps), $failed, number_format($balance()));

echo 'Fights created for the rehearsal: #'.$fightA->fight_no.' (settled wala), #'.$fightB->fight_no." (settled wala after regrade)\n";

exit($failed === 0 && empty($failing) ? 0 : 1);
