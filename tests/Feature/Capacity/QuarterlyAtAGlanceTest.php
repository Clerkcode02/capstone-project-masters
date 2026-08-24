<?php

use App\Domain\Capacity\Enums\PerformanceTier;
use App\Livewire\Dashboard\QuarterlyAtAGlance;
use App\Models\CapacityMetric;
use App\Models\Designation;
use App\Models\Role;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function quarterlySettings(): void
{
    Setting::create(['key' => 'perf_below_max', 'value' => '90', 'type' => 'decimal', 'group' => 'capacity', 'label' => 'Below max']);
    Setting::create(['key' => 'perf_over_min', 'value' => '110', 'type' => 'decimal', 'group' => 'capacity', 'label' => 'Over min']);
}

function quarterlyUser(string $roleName, string $email, ?Designation $designation = null): User
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

function quarterlyMetric(User $user, int $month, ?float $percentage, string $tier): CapacityMetric
{
    return CapacityMetric::create([
        'user_id' => $user->id,
        'period_type' => 'monthly',
        'period_year' => 2026,
        'period_month' => $month,
        'h_base' => 160,
        'h_leave' => 0,
        'h_poss' => 160,
        'u_target' => '0.800',
        'h_thresh' => 128,
        'h_prod' => $percentage ? round(128 * $percentage / 100, 2) : 0,
        'h_non_prod' => 0,
        'performance_percentage' => $percentage,
        'performance_tier' => $tier,
        'effective_availability_hours' => 0,
        'computed_at' => now(),
    ]);
}

it('denies employees from viewing the quarterly at-a-glance dashboard', function () {
    $employee = quarterlyUser('employee', 'employee@example.com');

    Livewire::actingAs($employee)
        ->test(QuarterlyAtAGlance::class)
        ->assertForbidden();
});

it('averages the three monthly percentages into the quarterly percentage and tier', function () {
    quarterlySettings();

    $designation = Designation::create(['name' => 'Reports Analyst', 'utilization_target' => '0.800']);
    $manager = quarterlyUser('manager', 'manager@example.com', $designation);
    $analyst = quarterlyUser('employee', 'analyst@example.com', $designation);

    // mean(87.5, 102.0, 95.5) = 95.00 -> acceptable
    quarterlyMetric($analyst, 1, 87.5, 'below');
    quarterlyMetric($analyst, 2, 102.0, 'acceptable');
    quarterlyMetric($analyst, 3, 95.5, 'acceptable');

    Livewire::actingAs($manager)
        ->test(QuarterlyAtAGlance::class)
        ->set('year', 2026)
        ->set('quarter', 1)
        ->assertOk();

    $component = Livewire::actingAs($manager)->test(QuarterlyAtAGlance::class)->set('year', 2026)->set('quarter', 1);
    $row = $component->get('rows')->firstWhere('user.id', $analyst->id);

    expect($row['quarterly_percentage'])->toBe(95.00);
    expect($row['quarterly_tier'])->toBe(PerformanceTier::Acceptable);
});

it('excludes a not_applicable month from the quarterly mean', function () {
    quarterlySettings();

    $designation = Designation::create(['name' => 'Reports Analyst', 'utilization_target' => '0.800']);
    $manager = quarterlyUser('manager', 'manager2@example.com', $designation);
    $analyst = quarterlyUser('employee', 'analyst2@example.com', $designation);

    quarterlyMetric($analyst, 1, null, 'not_applicable');
    quarterlyMetric($analyst, 2, 100.0, 'acceptable');
    quarterlyMetric($analyst, 3, 100.0, 'acceptable');

    $component = Livewire::actingAs($manager)->test(QuarterlyAtAGlance::class)->set('year', 2026)->set('quarter', 1);
    $row = $component->get('rows')->firstWhere('user.id', $analyst->id);

    expect($row['quarterly_percentage'])->toBe(100.00);
    expect($row['quarterly_tier'])->toBe(PerformanceTier::Acceptable);
});

it('marks the quarter not_applicable when all three months are excluded', function () {
    quarterlySettings();

    $designation = Designation::create(['name' => 'Reports Analyst', 'utilization_target' => '0.800']);
    $manager = quarterlyUser('manager', 'manager3@example.com', $designation);
    $analyst = quarterlyUser('employee', 'analyst3@example.com', $designation);

    quarterlyMetric($analyst, 1, null, 'not_applicable');
    quarterlyMetric($analyst, 2, null, 'not_applicable');
    quarterlyMetric($analyst, 3, null, 'not_applicable');

    $component = Livewire::actingAs($manager)->test(QuarterlyAtAGlance::class)->set('year', 2026)->set('quarter', 1);
    $row = $component->get('rows')->firstWhere('user.id', $analyst->id);

    expect($row['quarterly_percentage'])->toBeNull();
    expect($row['quarterly_tier'])->toBe(PerformanceTier::NotApplicable);
});
