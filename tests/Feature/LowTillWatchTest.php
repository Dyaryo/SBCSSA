<?php

namespace Tests\Feature;

use App\Models\TellerProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LowTillWatchTest extends TestCase
{
    use RefreshDatabase;

    private function supervisor(): User
    {
        return User::where('username', 'supervisor')->firstOrFail();
    }

    public function test_tills_below_the_threshold_are_listed_with_their_shortfall(): void
    {
        $this->seed();

        // Force the comparison: every till is "low" at this threshold.
        config(['pos.low_till_threshold' => 200000]);

        $teller = TellerProfile::where('teller_code', 'POS 001')->firstOrFail();
        $balance = (int) $teller->user->creditBalance();

        $this->actingAs($this->supervisor())
            ->get('/supervisor')
            ->assertOk()
            ->assertSee('POS tills running low')
            ->assertSee('data-low-till="POS 001"', false)
            ->assertSee(number_format(200000 - $balance));
    }

    public function test_a_healthy_roster_shows_the_nothing_to_top_up_state(): void
    {
        $this->seed();

        config(['pos.low_till_threshold' => 0]);

        $this->actingAs($this->supervisor())
            ->get('/supervisor')
            ->assertOk()
            ->assertSee('POS tills running low')
            ->assertSee('nothing to top up before the next event')
            ->assertDontSee('top up →');
    }

    public function test_the_card_tops_a_till_up_in_place(): void
    {
        $this->seed();

        config(['pos.low_till_threshold' => 200000]);

        $teller = TellerProfile::where('teller_code', 'POS 001')->firstOrFail();
        $balance = (int) $teller->user->creditBalance();
        $shortfall = 200000 - $balance;

        // The card offers the exact shortfall, posted straight at this till.
        $this->actingAs($this->supervisor())
            ->get('/supervisor')
            ->assertOk()
            ->assertSee('action="'.route('supervisor.accounts.credits', $teller->user_id).'"', false)
            ->assertSee('value="'.$shortfall.'"', false)
            ->assertSee('Low-till top-up from dashboard');

        $this->actingAs($this->supervisor())
            ->post('/supervisor/accounts/'.$teller->user_id.'/credits', [
                'amount' => $shortfall,
                'reason' => 'Low-till top-up from dashboard',
            ])
            ->assertRedirect()
            ->assertSessionHas('status');

        $this->assertSame(200000, (int) $teller->user->fresh()->creditBalance());

        // Funded to the line, the till drops out of the warning list.
        $this->actingAs($this->supervisor())
            ->get('/supervisor')
            ->assertOk()
            ->assertDontSee('data-low-till="POS 001"', false);
    }

    public function test_suspended_tills_are_not_flagged_for_a_top_up(): void
    {
        $this->seed();

        config(['pos.low_till_threshold' => 200000]);

        $teller = TellerProfile::where('teller_code', 'POS 002')->firstOrFail();
        $teller->status = 'suspended';
        $teller->save();

        $this->actingAs($this->supervisor())
            ->get('/supervisor')
            ->assertOk()
            ->assertSee('POS tills running low')
            ->assertSee('data-low-till="POS 001"', false)
            ->assertDontSee('data-low-till="POS 002"', false);
    }
}
