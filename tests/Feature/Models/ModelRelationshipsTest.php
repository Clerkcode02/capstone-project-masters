<?php

use App\Domain\Capacity\Enums\PerformanceTier;
use App\Domain\Identity\Enums\Role as RoleEnum;
use App\Domain\Optimization\Enums\RecommendationStatus;
use App\Domain\Optimization\Enums\TriggerType;
use App\Domain\Tasks\Enums\ComplexityTier;
use App\Domain\Tasks\Enums\TaskStatus;
use App\Domain\TimeTracking\Enums\EntryMethod;
use App\Domain\TimeTracking\Enums\HourType;
use App\Models\Account;
use App\Models\AuditLog;
use App\Models\CapacityMetric;
use App\Models\Designation;
use App\Models\MonthlyBaseline;
use App\Models\RedistributionRecommendation;
use App\Models\Role;
use App\Models\Setting;
use App\Models\Task;
use App\Models\TaskReassignment;
use App\Models\TimeLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('instantiates every model and resolves its key relationships', function () {
    $managerRole = Role::create([
        'name' => 'manager',
        'label' => 'Manager',
    ]);

    $employeeRole = Role::create([
        'name' => 'employee',
        'label' => 'Employee',
    ]);

    $designation = Designation::create([
        'name' => 'Reports Analyst',
        'utilization_target' => '0.800',
    ]);

    $manager = User::create([
        'first_name' => 'Maria',
        'last_name' => 'Santos',
        'email' => 'maria.santos@example.com',
        'password' => 'secret',
        'role_id' => $managerRole->id,
        'designation_id' => $designation->id,
    ]);

    $employee = User::create([
        'employee_code' => 'EMP-0001',
        'first_name' => 'Juan',
        'last_name' => 'Dela Cruz',
        'email' => 'juan.delacruz@example.com',
        'password' => 'secret',
        'role_id' => $employeeRole->id,
        'designation_id' => $designation->id,
        'manager_id' => $manager->id,
    ]);

    expect($employee->full_name)->toBe('Juan Dela Cruz')
        ->and($employee->manager->is($manager))->toBeTrue()
        ->and($manager->subordinates->pluck('id'))->toContain($employee->id)
        ->and($employee->role->is($employeeRole))->toBeTrue()
        ->and($employee->designation->is($designation))->toBeTrue()
        ->and($employee->isRole(RoleEnum::Employee))->toBeTrue();

    $account = Account::create([
        'name' => 'Agility PR Solutions',
        'code' => 'AGL-001',
        'expected_monthly_hours' => '160.00',
    ]);

    $account->users()->attach($employee->id, ['assigned_at' => now()]);

    expect($account->users->pluck('id'))->toContain($employee->id)
        ->and($employee->accounts->pluck('id'))->toContain($account->id);

    $baseline = MonthlyBaseline::create([
        'period_year' => 2026,
        'period_month' => 8,
        'baseline_hours' => '176.00',
        'set_by' => $manager->id,
    ]);

    expect($baseline->setBy->is($manager))->toBeTrue();

    $task = Task::create([
        'reference' => 'TSK-2026-0001',
        'title' => 'Weekly media clipping report',
        'account_id' => $account->id,
        'assigned_to' => $employee->id,
        'created_by' => $manager->id,
        'complexity_tier' => ComplexityTier::Medium,
        'complexity_weight' => 3,
        'standard_hours' => '4.00',
        'status' => TaskStatus::InProgress,
    ]);

    expect($task->complexity_tier)->toBe(ComplexityTier::Medium)
        ->and($task->status)->toBe(TaskStatus::InProgress)
        ->and($task->account->is($account))->toBeTrue()
        ->and($task->assignee->is($employee))->toBeTrue()
        ->and($task->creator->is($manager))->toBeTrue()
        ->and(Task::query()->active()->first()?->is($task))->toBeTrue()
        ->and(Task::query()->visibleTo($employee)->first()?->is($task))->toBeTrue()
        ->and(Task::query()->visibleTo($manager)->count())->toBe(1);

    $timeLog = TimeLog::create([
        'user_id' => $employee->id,
        'task_id' => $task->id,
        'account_id' => $account->id,
        'log_date' => '2026-08-05',
        'hour_type' => HourType::Production,
        'duration_minutes' => 120,
        'entry_method' => EntryMethod::Manual,
    ]);

    expect($timeLog->hour_type)->toBe(HourType::Production)
        ->and($timeLog->task->is($task))->toBeTrue()
        ->and($timeLog->user->is($employee))->toBeTrue()
        ->and($timeLog->account->is($account))->toBeTrue()
        ->and(TimeLog::query()->visibleTo($employee)->first()?->is($timeLog))->toBeTrue()
        ->and(TimeLog::query()->visibleTo($manager)->count())->toBe(1);

    $metric = CapacityMetric::create([
        'user_id' => $employee->id,
        'period_type' => 'monthly',
        'period_year' => 2026,
        'period_month' => 8,
        'h_base' => '176.00',
        'h_leave' => '0.00',
        'h_poss' => '176.00',
        'u_target' => '0.800',
        'h_thresh' => '140.80',
        'h_prod' => '2.00',
        'h_non_prod' => '0.00',
        'performance_tier' => PerformanceTier::Below,
        'effective_availability_hours' => '138.80',
        'computed_at' => now(),
    ]);

    expect($metric->performance_tier)->toBe(PerformanceTier::Below)
        ->and($metric->user->is($employee))->toBeTrue()
        ->and(CapacityMetric::query()->visibleTo($employee)->first()?->is($metric))->toBeTrue();

    $recommendation = RedistributionRecommendation::create([
        'task_id' => $task->id,
        'from_user_id' => $employee->id,
        'suggested_user_id' => $manager->id,
        'trigger_type' => TriggerType::Bottleneck,
        'actual_hours' => '8.00',
        'from_workload_score' => 15,
        'basis' => 'tier_history',
        'reason' => 'Actual hours exceed the tier historical average by more than the configured variance.',
        'status' => RecommendationStatus::Pending,
    ]);

    expect($recommendation->trigger_type)->toBe(TriggerType::Bottleneck)
        ->and($recommendation->status)->toBe(RecommendationStatus::Pending)
        ->and($recommendation->task->is($task))->toBeTrue()
        ->and($recommendation->fromUser->is($employee))->toBeTrue()
        ->and($recommendation->suggestedUser->is($manager))->toBeTrue();

    $reassignment = TaskReassignment::create([
        'task_id' => $task->id,
        'from_user_id' => $employee->id,
        'to_user_id' => $manager->id,
        'recommendation_id' => $recommendation->id,
        'performed_by' => $manager->id,
    ]);

    expect($reassignment->task->is($task))->toBeTrue()
        ->and($reassignment->fromUser->is($employee))->toBeTrue()
        ->and($reassignment->toUser->is($manager))->toBeTrue()
        ->and($reassignment->recommendation->is($recommendation))->toBeTrue()
        ->and($reassignment->performedBy->is($manager))->toBeTrue();

    $setting = Setting::create([
        'key' => 'workload_threshold',
        'value' => '12',
        'type' => 'int',
        'group' => 'workload',
        'label' => 'Workload Threshold',
        'updated_by' => $manager->id,
    ]);

    expect($setting->updatedBy->is($manager))->toBeTrue();

    $auditLog = AuditLog::create([
        'user_id' => $manager->id,
        'action' => 'task_created',
        'auditable_type' => Task::class,
        'auditable_id' => $task->id,
        'description' => 'Task created via test.',
    ]);

    expect($auditLog->user->is($manager))->toBeTrue()
        ->and($auditLog->auditable->is($task))->toBeTrue();
});
