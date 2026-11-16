<?php

namespace Tests\Feature;

use App\Models\Cock;
use App\Models\Event;
use App\Models\Fight;
use App\Models\GameMasterProfile;
use App\Models\User;
use App\Services\ReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ReportChainTest extends TestCase
{
    use RefreshDatabase;

    private function fight(): Fight
    {
        $gmUser = User::create([
            'name' => 'GM Report',
            'username' => 'gm.report',
            'password' => 'Password@123',
            'role' => User::ROLE_GAME_MASTER,
            'status' => User::STATUS_ACTIVE,
        ]);

        $gm = GameMasterProfile::create(['user_id' => $gmUser->id]);

        $event = Event::create([
            'code' => 'EVT-R1',
            'name' => 'Report Derby',
            'game_master_id' => $gm->id,
            'status' => Event::STATUS_OPEN,
        ]);

        return Fight::create([
            'event_id' => $event->id,
            'fight_no' => 1,
            'red_cock_id' => Cock::create(['name' => 'R'])->id,
            'blue_cock_id' => Cock::create(['name' => 'B'])->id,
            'status' => Fight::STATUS_SETTLED,
            'winner' => 'red',
        ]);
    }

    public function test_snapshots_are_linked_into_a_verified_chain(): void
    {
        $fight = $this->fight();
        $reports = app(ReportService::class);

        $first = $reports->snapshot($reports->build('fight', ['fight_id' => $fight->id]));
        $second = $reports->snapshot($reports->build('event', ['event_id' => $fight->event_id]));

        $this->assertNull($first->prev_hash);
        $this->assertSame($first->payload_hash, $second->prev_hash);

        $check = $reports->verifyChain();

        $this->assertSame([], $check['issues']);
        $this->assertSame(2, $check['checked']);
    }

    public function test_tampering_with_a_stored_payload_is_detected(): void
    {
        $fight = $this->fight();
        $reports = app(ReportService::class);

        $snapshot = $reports->snapshot($reports->build('fight', ['fight_id' => $fight->id]));

        DB::table('report_snapshots')->where('id', $snapshot->id)->update([
            'payload' => json_encode(['title' => 'Edited after the fact', 'rows' => []]),
        ]);

        $check = $reports->verifyChain();

        $this->assertNotSame([], $check['issues']);
    }
}
