<?php

use App\Models\Account;
use App\Models\CapacityMetric;
use App\Models\Designation;
use App\Models\Role;
use App\Models\Task;
use App\Models\TimeLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function dashboardUser(string $roleName, string $email, string $firstName, string $lastName, Designation $designation): User
{
    $role = Role::firstOrCreate(['name' => $roleName], ['label' => ucfirst($roleName)]);

    return User::create([
        'employee_code' => strtoupper($firstName).'-'.strtoupper($lastName),
        'first_name' => $firstName,
        'last_name' => $lastName,
        'email' => $email,
        'password' => 'secret',
        'role_id' => $role->id,
        'designation_id' => $designation->id,
        'is_active' => true,
    ]);
}

it('shows only the authenticated employee\'s own data, never a colleague\'s', function () {
    $designation = Designation::create(['name' => 'Reports Analyst', 'utilization_target' => '0.800']);
    $account = Account::create(['name' => 'Agility PR Solutions', 'code' => 'AGL-001']);

    $owner = dashboardUser('employee', 'juan@example.com', 'Juan', 'DelaCruz', $designation);
    $colleague = dashboardUser('employee', 'liza@example.com', 'Liza', 'Fernandez', $designation);

    $ownTask = Task::create([
        'reference' => 'TSK-0001',
        'title' => 'Own Media Monitoring Report',
        'account_id' => $account->id,
        'assigned_to' => $owner->id,
        'created_by' => $owner->id,
        'complexity_tier' => 'medium',
        'complexity_weight' => 3,
        'standard_hours' => 4,
        'status' => 'in_progress',
    ]);

    $colleagueTask = Task::create([
        'reference' => 'TSK-0002',
        'title' => 'Colleague Sentiment Analysis Deck',
        'account_id' => $account->id,
        'assigned_to' => $colleague->id,
        'created_by' => $colleague->id,
        'complexity_tier' => 'large',
        'complexity_weight' => 5,
        'standard_hours' => 8,
        'status' => 'in_progress',
    ]);

    TimeLog::create([
        'user_id' => $owner->id,
        'account_id' => $account->id,
        'log_date' => now()->startOfMonth()->addDays(2),
        'hour_type' => 'production',
        'duration_minutes' => 120,
        'entry_method' => 'manual',
    ]);

    TimeLog::create([
        'user_id' => $colleague->id,
        'account_id' => $account->id,
        'log_date' => now()->startOfMonth()->addDays(3),
        'hour_type' => 'production',
        'duration_minutes' => 480,
        'entry_method' => 'manual',
    ]);

    CapacityMetric::create([
        'user_id' => $owner->id,
        'period_type' => 'monthly',
        'period_year' => now()->year,
        'period_month' => now()->month,
        'h_base' => 160,
        'h_leave' => 0,
        'h_poss' => 160,
        'u_target' => '0.800',
        'h_thresh' => 128,
        'h_prod' => 100,
        'h_non_prod' => 0,
        'performance_percentage' => 78.13,
        'performance_tier' => 'below',
        'effective_availability_hours' => 28,
        'computed_at' => now(),
    ]);

    CapacityMetric::create([
        'user_id' => $colleague->id,
        'period_type' => 'monthly',
        'period_year' => now()->year,
        'period_month' => now()->month,
        'h_base' => 160,
        'h_leave' => 0,
        'h_poss' => 160,
        'u_target' => '0.800',
        'h_thresh' => 128,
        'h_prod' => 140,
        'h_non_prod' => 0,
        'performance_percentage' => 109.38,
        'performance_tier' => 'acceptable',
        'effective_availability_hours' => -12,
        'computed_at' => now(),
    ]);

    $response = $this->actingAs($owner)->get('/my/dashboard');

    $response->assertOk();

    // Own data is present.
    $response->assertSee($ownTask->reference);
    $response->assertSee($ownTask->title);
    $response->assertSee('78.13');

    // Nothing belonging to the colleague ever appears in the rendered HTML.
    $response->assertDontSee($colleagueTask->reference);
    $response->assertDontSee($colleagueTask->title);
    $response->assertDontSee('109.38');
    $response->assertDontSee($colleague->first_name);
    $response->assertDontSee($colleague->last_name);
    $response->assertDontSee($colleague->email);
});
