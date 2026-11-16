<?php

namespace Tests\Feature;

use App\Models\Fight;
use App\Models\Event;
use App\Models\User;
use App\Support\WinnersRoad;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WinnersRoadTest extends TestCase
{
    use RefreshDatabase;

    public function test_six_results_fill_the_column_then_a_seventh_runs_along_the_bottom(): void
    {
        $fight = fn (int $id, string $result) => (object) ['id' => $id, 'fight_no' => $id, 'result' => $result];

        $results = [];
        for ($i = 1; $i <= 7; $i++) {
            $results[] = $fight($i, 'meron');
        }
        $results[] = $fight(8, 'wala');

        $road = WinnersRoad::build(collect($results), 6, 16);

        $this->assertSame(6, $road['rows']);
        $this->assertSame(16, $road['cols']);

        // Six meron beads down the first column.
        for ($row = 0; $row < 6; $row++) {
            $this->assertSame('meron', $road['cells'][$row][0]->result);
        }

        // The seventh meron continues along the bottom, not a seventh layer.
        $this->assertSame('meron', $road['cells'][5][1]->result);

        // The other side starts again at the top of the next column.
        $this->assertSame('wala', $road['cells'][0][2]->result);
    }

    public function test_the_next_winner_returns_to_the_top_of_the_next_column(): void
    {
        $fight = fn (int $id, string $result) => (object) ['id' => $id, 'fight_no' => $id, 'result' => $result];

        $road = WinnersRoad::build(collect([
            $fight(1, 'meron'),
            $fight(2, 'meron'),
            $fight(3, 'wala'),
            $fight(4, 'meron'),
        ]), 6, 16);

        $this->assertSame('meron', $road['cells'][0][0]->result);
        $this->assertSame('meron', $road['cells'][1][0]->result);
        $this->assertSame('wala', $road['cells'][0][1]->result);
        $this->assertSame('meron', $road['cells'][0][2]->result);
    }

    public function test_the_game_master_dashboard_draws_the_road(): void
    {
        $this->seed();
        $gm = User::where('username', 'gm.north')->firstOrFail();

        $this->actingAs($gm)
            ->get('/gm')
            ->assertOk()
            ->assertSee('Winners road')
            ->assertSee('road-cell', false)
            ->assertSee('road-mark', false)
            ->assertSee('bead plate');
    }

    public function test_the_fight_book_draws_the_same_road(): void
    {
        $this->seed();
        $gm = User::where('username', 'gm.north')->firstOrFail();

        $settled = Fight::whereNotNull('result')->firstOrFail();

        $this->actingAs($gm)
            ->get('/gm/history')
            ->assertOk()
            ->assertSee('Winners road')
            ->assertSee('road-mark '.$settled->result, false)
            ->assertSee('Fight book');
    }

    public function test_the_board_and_console_draw_identical_markup(): void
    {
        $this->seed();
        $gm = User::where('username', 'gm.north')->firstOrFail();

        $board = $this->get('/board')->assertOk()->getContent();
        $console = $this->actingAs($gm)->get('/gm')->assertOk()->getContent();

        $this->assertStringContainsString('class="road-cell"', $board);
        $this->assertStringContainsString('class="road-cell"', $console);
        $this->assertStringContainsString('road-mark meron', $board);
        $this->assertStringContainsString('road-mark meron', $console);
    }
}
