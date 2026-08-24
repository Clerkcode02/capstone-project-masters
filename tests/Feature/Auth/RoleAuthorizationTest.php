<?php

namespace Tests\Feature\Auth;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Tests\TestCase;

class RoleAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    private function userWithRole(string $roleName): User
    {
        $role = Role::query()->firstOrCreate(
            ['name' => $roleName],
            ['label' => ucfirst($roleName)]
        );

        return User::factory()->create(['role_id' => $role->id]);
    }

    public function test_employee_is_forbidden_from_manager_routes(): void
    {
        $employee = $this->userWithRole('employee');

        $response = $this->actingAs($employee)->get('/manager/dashboard');

        $response->assertForbidden();
    }

    public function test_employee_is_forbidden_from_admin_routes(): void
    {
        $employee = $this->userWithRole('employee');

        $response = $this->actingAs($employee)->get('/admin/dashboard');

        $response->assertForbidden();
    }

    public function test_manager_is_forbidden_from_admin_routes(): void
    {
        $manager = $this->userWithRole('manager');

        $response = $this->actingAs($manager)->get('/admin/dashboard');

        $response->assertForbidden();
    }

    public function test_manager_can_access_manager_routes(): void
    {
        $manager = $this->userWithRole('manager');

        $response = $this->actingAs($manager)->get('/manager/dashboard');

        $response->assertOk();
    }

    public function test_administrator_can_access_manager_and_admin_routes(): void
    {
        $administrator = $this->userWithRole('administrator');

        $this->actingAs($administrator)->get('/manager/dashboard')->assertOk();
        $this->actingAs($administrator)->get('/admin/dashboard')->assertOk();
    }

    public function test_any_authenticated_user_can_access_my_routes(): void
    {
        $employee = $this->userWithRole('employee');

        $response = $this->actingAs($employee)->get('/my/dashboard');

        $response->assertOk();
    }

    public function test_administrator_is_redirected_to_admin_dashboard_after_login(): void
    {
        $administrator = $this->userWithRole('administrator');

        $response = $this->actingAs($administrator)->get('/dashboard');

        $response->assertRedirect(route('admin.dashboard'));
    }

    public function test_manager_is_redirected_to_manager_dashboard_after_login(): void
    {
        $manager = $this->userWithRole('manager');

        $response = $this->actingAs($manager)->get('/dashboard');

        $response->assertRedirect(route('manager.dashboard'));
    }

    public function test_employee_is_redirected_to_my_dashboard_after_login(): void
    {
        $employee = $this->userWithRole('employee');

        $response = $this->actingAs($employee)->get('/dashboard');

        $response->assertRedirect(route('my.dashboard'));
    }

    public function test_inactive_user_cannot_log_in(): void
    {
        $user = $this->userWithRole('employee');
        $user->update(['is_active' => false]);

        $component = Volt::test('pages.auth.login')
            ->set('form.email', $user->email)
            ->set('form.password', 'password');

        $component->call('login');

        $component->assertHasErrors();
        $this->assertGuest();
    }
}
