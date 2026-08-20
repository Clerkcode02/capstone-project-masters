<?php

namespace Tests\Feature\Accounts;

use App\Models\Account;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountAccessTest extends TestCase
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

    public function test_employee_cannot_see_an_account_they_are_not_assigned_to(): void
    {
        $employee = $this->userWithRole('employee');
        $account = Account::factory()->create();

        $response = $this->actingAs($employee)->get(route('my.accounts.show', $account));

        $response->assertForbidden();

        $indexResponse = $this->actingAs($employee)->get(route('my.accounts.index'));
        $indexResponse->assertOk();
        $indexResponse->assertDontSee($account->name);
    }

    public function test_employee_can_see_an_account_they_are_assigned_to(): void
    {
        $employee = $this->userWithRole('employee');
        $account = Account::factory()->create();
        $account->users()->attach($employee->id, ['assigned_at' => now()]);

        $response = $this->actingAs($employee)->get(route('my.accounts.show', $account));

        $response->assertOk();
        $response->assertSee($account->name);
    }

    public function test_manager_can_view_any_account_read_only(): void
    {
        $manager = $this->userWithRole('manager');
        $account = Account::factory()->create();

        $this->actingAs($manager)->get(route('manager.accounts.index'))->assertOk();
        $this->actingAs($manager)->get(route('manager.accounts.show', $account))->assertOk();
    }

    public function test_manager_cannot_create_accounts(): void
    {
        $manager = $this->userWithRole('manager');

        $response = $this->actingAs($manager)->post(route('admin.accounts.store'), [
            'name' => 'Should Fail',
            'code' => 'FAIL-001',
        ]);

        $response->assertForbidden();
    }

    public function test_administrator_can_create_update_and_delete_an_account(): void
    {
        $administrator = $this->userWithRole('administrator');

        $this->actingAs($administrator)->post(route('admin.accounts.store'), [
            'name' => 'Media Insights Group',
            'code' => 'MIG-001',
            'expected_monthly_hours' => 160,
        ])->assertRedirect(route('admin.accounts.index'));

        $account = Account::where('code', 'MIG-001')->firstOrFail();

        $this->actingAs($administrator)->put(route('admin.accounts.update', $account), [
            'name' => 'Media Insights Group PH',
            'code' => 'MIG-001',
            'expected_monthly_hours' => 176,
        ])->assertRedirect(route('admin.accounts.edit', $account));

        $this->assertSame('Media Insights Group PH', $account->fresh()->name);

        $this->actingAs($administrator)->delete(route('admin.accounts.destroy', $account))
            ->assertRedirect(route('admin.accounts.index'));

        $this->assertSoftDeleted($account);
    }

    public function test_administrator_can_assign_and_unassign_an_employee(): void
    {
        $administrator = $this->userWithRole('administrator');
        $employee = $this->userWithRole('employee');
        $account = Account::factory()->create();

        $this->actingAs($administrator)->post(route('admin.accounts.users.assign', $account), [
            'user_id' => $employee->id,
        ])->assertRedirect(route('admin.accounts.edit', $account));

        $this->assertTrue($account->users()->whereKey($employee->id)->exists());

        $this->actingAs($administrator)->delete(route('admin.accounts.users.unassign', [$account, $employee]))
            ->assertRedirect(route('admin.accounts.edit', $account));

        $this->assertFalse($account->users()->whereKey($employee->id)->exists());
    }
}
