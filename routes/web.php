<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\GameMaster\BetController as GameMasterBetController;
use App\Http\Controllers\GameMaster\DashboardController as GameMasterDashboardController;
use App\Http\Controllers\GameMaster\EventController;
use App\Http\Controllers\GameMaster\FightController;
use App\Http\Controllers\GameMaster\MonitorController;
use App\Http\Controllers\GameMaster\ReportController as GameMasterReportController;
use App\Http\Controllers\GameMaster\TellerController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\Pos\DashboardController as PosDashboardController;
use App\Http\Controllers\Supervisor\AccountController;
use App\Http\Controllers\Supervisor\AuditLogController;
use App\Http\Controllers\Supervisor\LiveController;
use App\Http\Controllers\Supervisor\MaintenanceController;
use App\Http\Controllers\Supervisor\MobileReportController;
use App\Http\Controllers\Supervisor\OverviewController;
use App\Http\Controllers\Supervisor\ReconciliationController;
use App\Http\Controllers\Supervisor\ReportController;
use App\Http\Controllers\Supervisor\ShiftController as SupervisorShiftController;
use App\Http\Controllers\Supervisor\WorkstationController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

// Pit-wall TV: public, read-only, no accounts and no slip data.
Route::get('board', [App\Http\Controllers\BoardController::class, 'index'])->name('board');
Route::get('board/feed', [App\Http\Controllers\BoardController::class, 'feed'])->name('board.feed');

Route::get('login', [AuthController::class, 'showLogin'])->name('login');
Route::post('login', [AuthController::class, 'login'])->middleware('throttle:20,1')->name('login.attempt');
Route::post('logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

Route::get('/', function () {
    return Auth::check() ? redirect()->route('home') : redirect()->route('login');
});

Route::middleware('auth')->group(function () {
    Route::get('/home', [HomeController::class, 'index'])->name('home');

    Route::middleware('role:supervisor')->prefix('supervisor')->name('supervisor.')->group(function () {
        Route::get('/', [OverviewController::class, 'index'])->name('dashboard');
        Route::get('/live', [LiveController::class, 'index'])->name('live');
        Route::get('/live/feed', [LiveController::class, 'feed'])->name('live.feed');
        Route::get('/mobile', [MobileReportController::class, 'index'])->name('mobile');
        Route::get('/mobile/feed', [MobileReportController::class, 'feed'])->name('mobile.feed');
        Route::get('/feed', [OverviewController::class, 'feed'])->name('feed');

        Route::get('/accounts', [AccountController::class, 'index'])->name('accounts');
        Route::post('/accounts', [AccountController::class, 'store'])->name('accounts.store');
        Route::patch('/accounts/{user}/status', [AccountController::class, 'updateStatus'])->name('accounts.status');
        Route::delete('/accounts/{user}', [AccountController::class, 'destroy'])->name('accounts.destroy');
        Route::post('/accounts/{user}/password', [AccountController::class, 'resetPassword'])->name('accounts.password');
        Route::post('/accounts/{user}/credits', [AccountController::class, 'grantCredits'])->name('accounts.credits');
        Route::get('/accounts/{user}/top-ups', [AccountController::class, 'topUps'])->name('accounts.topups');

        Route::get('/workstations', [WorkstationController::class, 'index'])->name('workstations');
        Route::post('/workstations', [WorkstationController::class, 'store'])->name('workstations.store');
        Route::patch('/workstations/{workstation}', [WorkstationController::class, 'update'])->name('workstations.update');

        Route::get('/shifts', [SupervisorShiftController::class, 'index'])->name('shifts');
        Route::get('/shifts/{shift}/closeout', [SupervisorShiftController::class, 'closeout'])->name('shifts.closeout');
        Route::get('/shifts/{shift}', [SupervisorShiftController::class, 'show'])->name('shifts.show');
        Route::post('/shifts/{shift}/close', [SupervisorShiftController::class, 'close'])->name('shifts.close');

        Route::get('/reports', [ReportController::class, 'index'])->name('reports');
        Route::post('/reports/snapshot', [ReportController::class, 'store'])->name('reports.snapshot');
        Route::get('/reports/csv', [ReportController::class, 'csv'])->name('reports.csv');
        Route::get('/reports/snapshots', [ReportController::class, 'snapshots'])->name('reports.snapshots');
        Route::get('/reports/snapshots/{snapshot}', [ReportController::class, 'showSnapshot'])->name('reports.snapshots.show');

        Route::get('/reconciliation', [ReconciliationController::class, 'index'])->name('reconciliation');
        Route::get('/reconciliation/csv', [ReconciliationController::class, 'csv'])->name('reconciliation.csv');

        Route::get('/audit', [AuditLogController::class, 'index'])->name('audit');

        Route::get('/maintenance', [MaintenanceController::class, 'index'])->name('maintenance');
        Route::post('/maintenance/reset', [MaintenanceController::class, 'reset'])->name('maintenance.reset');
    });

    Route::middleware('role:game_master')->prefix('gm')->name('gm.')->group(function () {
        Route::get('/', [GameMasterDashboardController::class, 'index'])->name('dashboard');
        Route::get('/feed', [GameMasterDashboardController::class, 'feed'])->name('feed');

        Route::get('/events', [EventController::class, 'index'])->name('events');
        Route::post('/events', [EventController::class, 'store'])->name('events.store');
        Route::get('/events/{event}/edit', [EventController::class, 'edit'])->name('events.edit');
        Route::patch('/events/{event}', [EventController::class, 'update'])->name('events.update');
        Route::post('/events/{event}/close', [EventController::class, 'close'])->name('events.close');
        Route::patch('/events/{event}/status', [EventController::class, 'updateStatus'])->name('events.status');

        Route::get('/fights', [FightController::class, 'index'])->name('fights');
        Route::post('/fights', [FightController::class, 'store'])->name('fights.store');
        Route::get('/fights/{fight}', [FightController::class, 'show'])->name('fights.show');
        Route::get('/fights/{fight}/history', [FightController::class, 'history'])->name('fights.history');
        Route::get('/fights/{fight}/feed', [FightController::class, 'feed'])->name('fights.feed');
        Route::post('/fights/{fight}/open', [FightController::class, 'openBetting'])->name('fights.open');
        Route::post('/fights/{fight}/lock', [FightController::class, 'lockBetting'])->name('fights.lock');
        Route::post('/fights/{fight}/start', [FightController::class, 'start'])->name('fights.start');
        Route::post('/fights/{fight}/side-lock', [FightController::class, 'sideLock'])->name('fights.side-lock');
        Route::post('/fights/{fight}/settle', [FightController::class, 'settle'])->name('fights.settle');
        Route::post('/fights/{fight}/void', [FightController::class, 'void'])->name('fights.void');
        Route::post('/fights/{fight}/result', [FightController::class, 'result'])->name('fights.result');
        Route::post('/fights/{fight}/regrade', [FightController::class, 'regrade'])->name('fights.regrade');
        Route::post('/fights/{fight}/next', [FightController::class, 'next'])->name('fights.next');

        Route::get('/tellers', [TellerController::class, 'index'])->name('tellers');
        // Read-only POS roster: accounts, credits, password resets, suspensions
        // and shift closures belong to the Supervisor.
        Route::get('/shifts/{shift}/closeout', [TellerController::class, 'closeout'])->name('shifts.closeout');

        Route::get('/monitor', [MonitorController::class, 'index'])->name('monitor');
        Route::post('/monitor/bind', [MonitorController::class, 'bindDevice'])->name('monitor.bind');

        Route::get('/bets', [GameMasterBetController::class, 'index'])->name('bets');
        Route::post('/bets/{bet}/void', [GameMasterBetController::class, 'voidBet'])->name('bets.void');

        Route::get('/report', [GameMasterReportController::class, 'index'])->name('report');
        Route::get('/history', [FightController::class, 'fightHistory'])->name('history');
    });

    Route::middleware('role:teller')->prefix('pos')->name('pos.')->group(function () {
        Route::get('/', [PosDashboardController::class, 'index'])->name('dashboard');
        Route::post('/shift/open', [PosDashboardController::class, 'openShift'])->name('shift.open');
        Route::post('/shift/close', [PosDashboardController::class, 'closeShift'])->name('shift.close');
        Route::post('/bets', [PosDashboardController::class, 'placeBet'])->name('bets.store');
        Route::get('/history', [PosDashboardController::class, 'history'])->name('history');
        Route::get('/fights-feed', [PosDashboardController::class, 'fightFeed'])->name('fights.feed');
        Route::get('/fights-window', [PosDashboardController::class, 'fightWindow'])->name('fights.window');
        Route::get('/lookup', [PosDashboardController::class, 'lookup'])->name('lookup');
        Route::post('/bets/{bet}/payout', [PosDashboardController::class, 'recordPayout'])->name('bets.payout');
        Route::get('/bets/{bet}/cancel', [PosDashboardController::class, 'cancelForm'])->name('bets.cancel.form');
        Route::post('/bets/{bet}/cancel', [PosDashboardController::class, 'cancelBet'])->name('bets.cancel');
        Route::get('/bets/{bet}/cancellation', [PosDashboardController::class, 'cancellationSlip'])->name('bets.cancellation');
        Route::get('/bets/{bet}/receipt', [PosDashboardController::class, 'receipt'])->name('bets.receipt');
        Route::get('/bets/{bet}/claim', [PosDashboardController::class, 'claim'])->name('bets.claim');
        Route::get('/shifts/{shift}/closeout', [PosDashboardController::class, 'closeout'])->name('shifts.closeout');
    });
});
