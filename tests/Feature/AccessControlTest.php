<?php

namespace Tests\Feature;

use App\Models\GameMasterProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccessControlTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $username, string $role): User
    {
        return User::create([
            'name' => $username,
            'username' => $username,
            'password' => 'Password@123',
            'role' => $role,
            'status' => User::STATUS_ACTIVE,
        ]);
    }

    public function test_guests_are_redirected_to_the_login_page(): void
    {
        $this->get('/supervisor')->assertRedirect(route('login'));
        $this->get('/gm')->assertRedirect(route('login'));
        $this->get('/pos')->assertRedirect(route('login'));
    }

    public function test_a_teller_cannot_open_game_master_screens(): void
    {
        $teller = $this->user('teller1', User::ROLE_TELLER);

        $this->actingAs($teller)->get('/gm')->assertForbidden();
        $this->actingAs($teller)->get('/supervisor')->assertForbidden();
    }

    public function test_a_game_master_cannot_open_supervisor_screens(): void
    {
        $gm = $this->user('gm1', User::ROLE_GAME_MASTER);
        GameMasterProfile::create(['user_id' => $gm->id]);

        $this->actingAs($gm)->get('/supervisor')->assertForbidden();
        $this->actingAs($gm)->get('/gm')->assertOk();
    }

    public function test_a_supervisor_can_open_supervisor_screens(): void
    {
        $supervisor = $this->user('super1', User::ROLE_SUPERVISOR);

        $this->actingAs($supervisor)->get('/supervisor')->assertOk();
        $this->actingAs($supervisor)->get('/supervisor/accounts')->assertOk();
        $this->actingAs($supervisor)->get('/supervisor/reports')->assertOk();
        $this->actingAs($supervisor)->get('/supervisor/audit')->assertOk();
    }

    public function test_a_suspended_account_is_rejected(): void
    {
        $user = $this->user('suspended1', User::ROLE_SUPERVISOR);
        $user->status = User::STATUS_SUSPENDED;
        $user->save();

        $this->actingAs($user)->get('/supervisor')->assertForbidden();
    }

    public function test_login_requires_valid_credentials(): void
    {
        $this->user('supervisor1', User::ROLE_SUPERVISOR);

        $this->post('/login', [
            'username' => 'supervisor1',
            'password' => 'wrong-password',
        ])->assertSessionHasErrors('username');

        $this->assertGuest();
    }
}
