<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BoardScoreboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_board_shows_total_value_and_total_payout_for_each_side(): void
    {
        $this->seed();

        $this->get('/board')
            ->assertOk()
            ->assertSee('Total payout')
            ->assertSee('Total pool')
            ->assertSee('pool-amount', false)
            ->assertSee('Total value')
            ->assertSee('Minimum bet')
            ->assertSee('payout', false)
            ->assertSee('1–7')
            ->assertSee('0.00');
    }
}
