<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccessControlTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(string $role, bool $active = true): User
    {
        return User::create([
            'name' => $role,
            'email' => $role.'@test.local',
            'password' => 'secret-1234',
            'role' => $role,
            'is_active' => $active,
        ]);
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get(route('dashboard'))->assertRedirect(route('login'));
        $this->get(route('billing.index'))->assertRedirect(route('login'));
    }

    public function test_operator_cannot_access_admin_area(): void
    {
        $operator = $this->makeUser(User::ROLE_OPERATOR);

        $this->actingAs($operator)->get(route('owners.index'))->assertForbidden();
        $this->actingAs($operator)->get(route('users.index'))->assertForbidden();
        $this->actingAs($operator)->get(route('tariffs.index'))->assertForbidden();

        // اما بخش عملیاتی باز است
        $this->actingAs($operator)->get(route('cargo.index'))->assertOk();
        $this->actingAs($operator)->get(route('billing.index'))->assertOk();
    }

    public function test_admin_can_access_admin_area(): void
    {
        $admin = $this->makeUser(User::ROLE_ADMIN);

        $this->actingAs($admin)->get(route('owners.index'))->assertOk();
        $this->actingAs($admin)->get(route('users.index'))->assertOk();
        $this->actingAs($admin)->get(route('tariffs.index'))->assertOk();
        $this->actingAs($admin)->get(route('tax.edit'))->assertOk();
    }

    public function test_inactive_user_cannot_login(): void
    {
        $this->makeUser(User::ROLE_OPERATOR, false);

        $this->post(route('login.submit'), [
            'email' => 'operator@test.local',
            'password' => 'secret-1234',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_inactive_user_is_logged_out_of_existing_session(): void
    {
        $user = $this->makeUser(User::ROLE_OPERATOR, false);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }

    public function test_admin_cannot_deactivate_own_account(): void
    {
        $admin = $this->makeUser(User::ROLE_ADMIN);

        $this->actingAs($admin)
            ->patch(route('users.toggle', $admin))
            ->assertRedirect();

        $this->assertTrue($admin->fresh()->is_active);
    }
}
