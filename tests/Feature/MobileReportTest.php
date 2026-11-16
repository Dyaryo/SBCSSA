<?php

namespace Tests\Feature;

use App\Models\Event;
use App\Models\Fight;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MobileReportTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_mobile_report_shows_the_overall_summary_draw_and_cancelled(): void
    {
        $this->seed();
        $supervisor = User::where('username', 'supervisor')->firstOrFail();

        $this->actingAs($supervisor)
            ->get('/supervisor/mobile')
            ->assertOk()
            ->assertSee('Overall Summary')
            ->assertSee('Draw')
            ->assertSee('Cancelled')
            ->assertSee('Block')
            ->assertSee('Next report')
            ->assertSee('manifest.webmanifest', false)
            ->assertSee('data-live-refresh', false)
            ->assertSee('Install on your phone');
    }

    public function test_the_block_report_advances_as_fights_are_graded(): void
    {
        $this->seed();
        $supervisor = User::where('username', 'supervisor')->firstOrFail();
        $gm = User::where('username', 'gm.north')->firstOrFail();

        $event = Event::create([
            'code' => 'REG-MOB',
            'name' => 'Mobile Card',
            'type' => Event::TYPE_REGULAR,
            'game_master_id' => $gm->gameMasterProfile->id,
            'starts_at' => now(),
            'status' => Event::STATUS_OPEN,
        ]);

        // Close out the seeded block so the next ten fights form a fresh one.
        $graded = Fight::whereNotNull('result')->count();
        $filler = 1;

        while (Fight::whereNotNull('result')->count() % 10 !== 0) {
            Fight::create([
                'event_id' => $event->id,
                'fight_no' => 200 + $filler++,
                'odds_red' => 1.850,
                'odds_blue' => 2.050,
                'status' => Fight::STATUS_SETTLED,
                'result' => Fight::RESULT_MERON,
                'declared_at' => now(),
            ]);
        }

        for ($i = 1; $i <= 10; $i++) {
            Fight::create([
                'event_id' => $event->id,
                'fight_no' => 100 + $i,
                'odds_red' => 1.850,
                'odds_blue' => 2.050,
                'status' => Fight::STATUS_SETTLED,
                'result' => $i % 5 === 0 ? Fight::RESULT_DRAW : ($i % 7 === 0 ? Fight::RESULT_CANCELLED : Fight::RESULT_MERON),
                'declared_at' => now(),
            ]);
        }

        $response = $this->actingAs($supervisor)->get('/supervisor/mobile')->assertOk();

        $response->assertSee('Block 2')
            ->assertSee('Block complete')
            ->assertSee('fights')
            ->assertSee('Mobile Card')
            ->assertSee('DRAW')
            ->assertSee('CANCELLED');

        $this->assertGreaterThan($graded, Fight::whereNotNull('result')->count());
    }

    public function test_the_mobile_feed_changes_and_is_supervisor_only(): void
    {
        $this->seed();
        $supervisor = User::where('username', 'supervisor')->firstOrFail();
        $teller = User::where('username', 'pos01')->firstOrFail();

        $before = $this->actingAs($supervisor)->getJson('/supervisor/mobile/feed')->assertOk()->json('signature');

        $gm = User::where('username', 'gm.north')->firstOrFail();
        $event = Event::create([
            'code' => 'REG-MOB2',
            'name' => 'Feed Card',
            'type' => Event::TYPE_REGULAR,
            'game_master_id' => $gm->gameMasterProfile->id,
            'starts_at' => now(),
            'status' => Event::STATUS_OPEN,
        ]);

        Fight::create([
            'event_id' => $event->id,
            'fight_no' => 50,
            'odds_red' => 1.800,
            'odds_blue' => 2.000,
            'status' => Fight::STATUS_SETTLED,
            'result' => Fight::RESULT_WALA,
            'declared_at' => now(),
        ]);

        $after = $this->actingAs($supervisor)->getJson('/supervisor/mobile/feed')->assertOk()->json('signature');

        $this->assertNotSame($before, $after);

        $this->actingAs($teller)->get('/supervisor/mobile')->assertForbidden();
    }

    public function test_the_manifest_and_service_worker_exist(): void
    {
        $manifest = public_path('manifest.webmanifest');
        $worker = public_path('sw.js');

        $this->assertFileExists($manifest);
        $this->assertFileExists($worker);
        $this->assertStringContainsString('E-Sabong Overall Report', (string) file_get_contents($manifest));
    }
}
