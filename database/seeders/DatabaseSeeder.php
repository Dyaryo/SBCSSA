<?php

namespace Database\Seeders;

use App\Models\Cock;
use App\Models\Event;
use App\Models\Fight;
use App\Models\GameMasterProfile;
use App\Models\ShiftSession;
use App\Models\TellerProfile;
use App\Models\User;
use App\Models\Workstation;
use App\Services\BettingService;
use App\Services\ReportService;
use App\Services\WalletService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        if (User::where('username', 'supervisor')->exists()) {
            $this->command->warn('Demo data already present — seed skipped.');

            return;
        }

        $wallets = app(WalletService::class);
        $betting = app(BettingService::class);
        $reports = app(ReportService::class);

        DB::transaction(function () use ($wallets, $betting, $reports) {
            // ---------------- accounts ----------------
            $supervisor = User::create([
                'name' => 'System Supervisor',
                'username' => 'supervisor',
                'password' => 'Admin@2026',
                'role' => User::ROLE_SUPERVISOR,
                'status' => User::STATUS_ACTIVE,
            ]);
            $supervisor->walletOrCreate();

            $gmNorthUser = User::create([
                'name' => 'Marco Reyes',
                'username' => 'gm.north',
                'password' => 'Master@2026',
                'role' => User::ROLE_GAME_MASTER,
                'status' => User::STATUS_ACTIVE,
                'created_by' => $supervisor->id,
            ]);
            $gmNorth = GameMasterProfile::create([
                'user_id' => $gmNorthUser->id,
                'employee_no' => 'GM-001',
                'contact' => 'North Arena pit',
            ]);

            $gmSouthUser = User::create([
                'name' => 'Liza Cruz',
                'username' => 'gm.south',
                'password' => 'Master@2026',
                'role' => User::ROLE_GAME_MASTER,
                'status' => User::STATUS_ACTIVE,
                'created_by' => $supervisor->id,
            ]);
            GameMasterProfile::create([
                'user_id' => $gmSouthUser->id,
                'employee_no' => 'GM-002',
                'contact' => 'South Arena pit',
            ]);

            // ---------------- workstations + tellers ----------------
            $tellerSpecs = [
                ['pos01', 'Ana Dela Cruz', 'POS 001', 'pos-north-1', 'Counter 1 — North', $gmNorth],
                ['pos02', 'Ben Santos', 'POS 002', 'pos-north-2', 'Counter 2 — North', $gmNorth],
                ['pos03', 'Cora Lim', 'POS 003', 'pos-south-1', 'Counter 1 — South', GameMasterProfile::where('employee_no', 'GM-002')->first()],
            ];

            $tellers = [];

            foreach ($tellerSpecs as $index => [$username, $name, $code, $device, $label, $gm]) {
                $workstation = Workstation::create([
                    'code' => str_replace(' ', '-', $code),
                    'label' => $label,
                    'device_uuid' => $device,
                    'approved_at' => now(),
                    'last_seen_at' => now(),
                ]);

                $user = User::create([
                    'name' => $name,
                    'username' => $username,
                    'password' => 'Teller@2026',
                    'role' => User::ROLE_TELLER,
                    'status' => User::STATUS_ACTIVE,
                    'created_by' => $supervisor->id,
                ]);

                $teller = TellerProfile::create([
                    'user_id' => $user->id,
                    'teller_code' => $code,
                    'game_master_id' => $gm->id,
                    'workstation_id' => $workstation->id,
                    'status' => 'active',
                ]);

                $workstation->assigned_teller_id = $teller->id;
                $workstation->save();

                $user->walletOrCreate();
                $wallets->seedCredits($user, 150000, $supervisor, 'Demo credit allocation');

                ShiftSession::create([
                    'teller_id' => $teller->id,
                    'workstation_id' => $workstation->id,
                    'opened_at' => now()->subHours(2),
                    'opening_note' => 'Seeded demo shift',
                    'status' => ShiftSession::STATUS_OPEN,
                ]);

                $tellers[$username] = [$user, $teller];
            }

            // ---------------- fighters ----------------
            $cockNames = [
                ['Thunder', 'Kelso', 2100, 'R. Bautista'],
                ['Lightning', 'Hatch', 2050, 'D. Villanueva'],
                ['Storm', 'Sweater', 2200, 'J. Aquino'],
                ['Blaze', 'Roundhead', 2150, 'F. Mercado'],
                ['Titan', 'Kelso', 2300, 'M. Ocampo'],
                ['Viper', 'Hatch', 2250, 'A. Salazar'],
                ['Falcon', 'Asil', 2000, 'P. Navarro'],
                ['Rogue', 'Sweater', 2080, 'E. Domingo'],
            ];

            $cocks = [];

            foreach ($cockNames as $i => [$name, $breed, $weight, $owner]) {
                $cocks[$name] = Cock::create([
                    'name' => $name,
                    'breed' => $breed,
                    'weight_grams' => $weight,
                    'owner_name' => $owner,
                    'band_number' => 'B-'.str_pad((string) ($i + 1), 3, '0', STR_PAD_LEFT),
                    'status' => 'available',
                ]);
            }

            // ---------------- events + fights ----------------
            $event = Event::create([
                'code' => 'EVT-0101',
                'name' => 'Friday Night Derby',
                'venue' => 'North Arena',
                'game_master_id' => $gmNorth->id,
                'starts_at' => now()->subHour(),
                'status' => Event::STATUS_OPEN,
            ]);

            Event::create([
                'code' => 'EVT-0102',
                'name' => 'South Side Showdown',
                'venue' => 'South Arena',
                'game_master_id' => GameMasterProfile::where('employee_no', 'GM-002')->first()->id,
                'starts_at' => now()->addDay(),
                'status' => Event::STATUS_DRAFT,
            ]);

            $pairings = [
                ['Thunder', 'Lightning', 1.900, 1.950, now()->subMinutes(30)],
                ['Storm', 'Blaze', 1.750, 2.100, now()->addMinutes(15)],
                ['Titan', 'Viper', 1.850, 2.000, now()->addMinutes(45)],
                ['Falcon', 'Rogue', 1.950, 1.900, now()->addMinutes(75)],
            ];

            $fights = [];

            foreach ($pairings as $i => [$red, $blue, $oddsRed, $oddsBlue, $scheduled]) {
                $fights[] = Fight::create([
                    'event_id' => $event->id,
                    'fight_no' => $i + 1,
                    'red_cock_id' => $cocks[$red]->id,
                    'blue_cock_id' => $cocks[$blue]->id,
                    'scheduled_at' => $scheduled,
                    'odds_red' => $oddsRed,
                    'odds_blue' => $oddsBlue,
                    'status' => Fight::STATUS_SCHEDULED,
                ]);
            }

            // ---------------- sample activity ----------------
            [$pos01User] = $tellers['pos01'];
            [$pos02User] = $tellers['pos02'];

            // Fight 1: opened, bet on, locked, settled red.
            $betting->openBetting($fights[0], $gmNorthUser);
            $betting->placeBet($pos01User, $fights[0], Fight::SIDE_RED, 500);
            $betting->placeBet($pos02User, $fights[0], Fight::SIDE_BLUE, 750);
            $betting->lockBetting($fights[0], $gmNorthUser);
            $betting->settle($fights[0], Fight::SIDE_RED, $gmNorthUser);

            // Fight 2: open and accepting bets right now.
            $betting->openBetting($fights[1], $gmNorthUser);
            $betting->placeBet($pos01User, $fights[1], Fight::SIDE_RED, 300);
            $betting->placeBet($pos02User, $fights[1], Fight::SIDE_BLUE, 400);

            // Seal a report for the settled fight.
            $reports->snapshot($reports->build('fight', ['fight_id' => $fights[0]->id]), $supervisor);
        });

        $this->command->info('Seeded demo data.');
        $this->command->table(
            ['Role', 'Username', 'Password'],
            [
                ['Supervisor', 'supervisor', 'Admin@2026'],
                ['Game Master', 'gm.north / gm.south', 'Master@2026'],
                ['POS Teller', 'pos01 / pos02 / pos03', 'Teller@2026'],
            ]
        );
    }
}
