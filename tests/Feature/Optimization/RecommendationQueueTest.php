<?php

use App\Livewire\Optimization\RecommendationQueue;
use App\Models\Account;
use App\Models\AuditLog;
use App\Models\Designation;
use App\Models\RedistributionRecommendation;
use App\Models\Role;
use App\Models\Task;
use App\Models\TaskReassignment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function optimizationDesignation(): Designation
{
    return Designation::create(['name' => 'Reports Analyst', 'utilization_target' => '0.800']);
}

function optimizationUser(string $roleName, string $email, Designation $designation): User
{
    $role = Role::firstOrCreate(['name' => $roleName], ['label' => ucfirst($roleName)]);

    return User::create([
        'first_name' => 'Test',
        'last_name' => ucfirst($roleName),
        'email' => $email,
        'password' => 'secret',
        'role_id' => $role->id,
        'designation_id' => $designation->id,
        'is_active' => true,
    ]);
}

function optimizationRecommendation(User $from, User $suggested, Task $task): RedistributionRecommendation
{
    return RedistributionRecommendation::create([
        'task_id' => $task->id,
        'from_user_id' => $from->id,
        'suggested_user_id' => $suggested->id,
        'trigger_type' => 'bottleneck',
        'actual_hours' => '8.00',
        'historical_avg_hours' => '4.00',
        'variance_percentage' => '100.00',
        'from_workload_score' => 14,
        'suggested_workload_score' => 4,
        'basis' => 'tier_history',
        'reason' => 'Task took double the historical average; suggested assignee has spare capacity.',
        'status' => 'pending',
    ]);
}

it('lets a manager accept a recommendation and produces exactly one reassignment and one audit row', function () {
    $designation = optimizationDesignation();
    $manager = optimizationUser('manager', 'manager@example.com', $designation);
    $from = optimizationUser('employee', 'from@example.com', $designation);
    $suggested = optimizationUser('employee', 'suggested@example.com', $designation);
    $account = Account::create(['name' => 'Agility PR Solutions', 'code' => 'AGL-001']);

    $task = Task::create([
        'reference' => 'TSK-2026-0001',
        'title' => 'Weekly media clipping report',
        'account_id' => $account->id,
        'assigned_to' => $from->id,
        'created_by' => $manager->id,
        'complexity_tier' => 'large',
        'complexity_weight' => 5,
        'standard_hours' => '4.00',
        'status' => 'in_progress',
    ]);

    $recommendation = optimizationRecommendation($from, $suggested, $task);

    Livewire::actingAs($manager)
        ->test(RecommendationQueue::class)
        ->call('confirmAccept', $recommendation->id)
        ->set('reason', 'Juan is over threshold; Maria has spare capacity this month.')
        ->call('accept')
        ->assertHasNoErrors();

    expect(TaskReassignment::count())->toBe(1);
    $reassignment = TaskReassignment::first();
    expect($reassignment->task_id)->toBe($task->id)
        ->and($reassignment->from_user_id)->toBe($from->id)
        ->and($reassignment->to_user_id)->toBe($suggested->id)
        ->and($reassignment->recommendation_id)->toBe($recommendation->id)
        ->and($reassignment->performed_by)->toBe($manager->id);

    expect($task->fresh()->assigned_to)->toBe($suggested->id);
    expect($recommendation->fresh()->status->value)->toBe('accepted');

    expect(AuditLog::where('action', 'recommendation_accepted')->count())->toBe(1);
});

it('lets a manager dismiss a recommendation without changing the task', function () {
    $designation = optimizationDesignation();
    $manager = optimizationUser('manager', 'manager@example.com', $designation);
    $from = optimizationUser('employee', 'from@example.com', $designation);
    $suggested = optimizationUser('employee', 'suggested@example.com', $designation);
    $account = Account::create(['name' => 'Agility PR Solutions', 'code' => 'AGL-001']);

    $task = Task::create([
        'reference' => 'TSK-2026-0002',
        'title' => 'Sentiment analysis batch',
        'account_id' => $account->id,
        'assigned_to' => $from->id,
        'created_by' => $manager->id,
        'complexity_tier' => 'medium',
        'complexity_weight' => 3,
        'standard_hours' => '3.00',
        'status' => 'in_progress',
    ]);

    $recommendation = optimizationRecommendation($from, $suggested, $task);

    Livewire::actingAs($manager)
        ->test(RecommendationQueue::class)
        ->call('confirmDismiss', $recommendation->id)
        ->call('dismiss')
        ->assertHasNoErrors();

    expect(TaskReassignment::count())->toBe(0);
    expect($task->fresh()->assigned_to)->toBe($from->id);
    expect($recommendation->fresh()->status->value)->toBe('dismissed');

    expect(AuditLog::where('action', 'recommendation_dismissed')->count())->toBe(1);
});

it('forbids an employee from reaching the recommendation queue', function () {
    $designation = optimizationDesignation();
    $employee = optimizationUser('employee', 'employee@example.com', $designation);
    $from = optimizationUser('employee', 'from@example.com', $designation);
    $suggested = optimizationUser('employee', 'suggested@example.com', $designation);
    $account = Account::create(['name' => 'Agility PR Solutions', 'code' => 'AGL-001']);

    $task = Task::create([
        'reference' => 'TSK-2026-0003',
        'title' => 'Toning review batch',
        'account_id' => $account->id,
        'assigned_to' => $from->id,
        'created_by' => $employee->id,
        'complexity_tier' => 'small',
        'complexity_weight' => 1,
        'standard_hours' => '1.00',
        'status' => 'in_progress',
    ]);

    optimizationRecommendation($from, $suggested, $task);

    Livewire::actingAs($employee)
        ->test(RecommendationQueue::class)
        ->assertStatus(403);

    $this->actingAs($employee)
        ->get(route('optimization.recommendations'))
        ->assertStatus(403);
});
