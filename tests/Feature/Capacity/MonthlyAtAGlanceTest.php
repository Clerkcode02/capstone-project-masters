<?php

use App\Livewire\Dashboard\MonthlyAtAGlance;
use App\Models\CapacityMetric;
use App\Models\Designation;
use App\Models\MonthlyBaseline;
use App\Models\Role;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function glanceSettings(): void
{
    Setting::create(['key' => 'perf_below_max', 'value' => '90', 'type' => 'decimal', 'group' => 'capacity', 'label' => 'Below max']);
    Setting::create(['key' => 'perf_over_min', 'value' => '110', 'type' => 'decimal', 'group' => 'capacity', 'label' => 'Over min']);
}

function glanceUser(string $roleName, string $email, ?Designation $designation = null): User
{
    $role = Role::firstOrCreate(['name' => $roleName], ['label' => ucfirst($roleName)]);

    return User::create([
        'first_name' => 'Test',
        'last_name' => ucfirst($roleName),
        'email' => $email,
        'password' => 'secret',
        'role_id' => $role->id,
        'designation_id' => $designation?->id,
        'is_active' => true,
    ]);
}

it('denies employees from viewing the monthly at-a-glance dashboard', function () {
    $employee = glanceUser('employee', 'employee@example.com');

    Livewire::actingAs($employee)
        ->test(MonthlyAtAGlance::class)
        ->assertForbidden();
});

it('lets a manager view metrics and see the summary stat counts', function () {
    glanceSettings();
    MonthlyBaseline::create(['period_year' => now()->year, 'period_month' => now()->month, 'baseline_hours' => 160]);

    $designation = Designation::create(['name' => 'Reports Analyst', 'utilization_target' => '0.800']);
    $manager = glanceUser('manager', 'manager@example.com', $designation);
    $analyst = glanceUser('employee', 'analyst@example.com', $designation);

    CapacityMetric::create([
        'user_id' => $analyst->id,
        'period_type' => 'monthly',
        'period_year' => now()->year,
        'period_month' => now()->month,
        'h_base' => 160,
        'h_leave' => 16,
        'h_poss' => 144,
        'u_target' => '0.800',
        'h_thresh' => 115.20,
        'h_prod' => 100.80,
        'h_non_prod' => 0,
        'performance_percentage' => 87.50,
        'performance_tier' => 'below',
        'effective_availability_hours' => 14.40,
        'computed_at' => now(),
    ]);

    Livewire::actingAs($manager)
        ->test(MonthlyAtAGlance::class)
        ->assertOk()
        ->assertSet('teamSize', 1)
        ->assertSet('underUtilizedCount', 1)
        ->assertSet('onTargetCount', 0);
});

it('lets a manager recalculate and regenerate capacity_metrics from time logs', function () {
    glanceSettings();
    MonthlyBaseline::create(['period_year' => now()->year, 'period_month' => now()->month, 'baseline_hours' => 160]);

    $designation = Designation::create(['name' => 'Reports Analyst', 'utilization_target' => '0.800']);
    $manager = glanceUser('manager', 'manager2@example.com', $designation);

    Livewire::actingAs($manager)
        ->test(MonthlyAtAGlance::class)
        ->call('recalculate')
        ->assertSet('recalculating', false);

    expect(CapacityMetric::where('user_id', $manager->id)->exists())->toBeTrue();
});
