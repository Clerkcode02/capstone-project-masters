<?php

use App\Domain\Tasks\Enums\ComplexityTier;
use App\Domain\Tasks\Enums\TaskStatus;
use App\Domain\TimeTracking\Enums\EntryMethod;
use App\Domain\TimeTracking\Enums\HourType;
use App\Domain\TimeTracking\Services\TimeLogService;
use App\Domain\TimeTracking\Services\TimerService;
use App\Models\Account;
use App\Models\Role;
use App\Models\Setting;
use App\Models\Task;
use App\Models\TimeLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

uses(RefreshDatabase::class);

function makeEmployee(string $email): User
{
    $role = Role::query()->firstOrCreate(['name' => 'employee'], ['label' => 'Employee']);

    return User::factory()->create(['email' => $email, 'role_id' => $role->id]);
}

function timeLogValidationRules(): array
{
    return [
        'log_date' => ['required', 'date', 'before_or_equal:today'],
        'duration_minutes' => ['required', 'integer', 'min:1', 'max:1440'],
        'hour_type' => ['required', Rule::enum(HourType::class)],
    ];
}

it('rejects a duration of zero', function () {
    $validator = Validator::make([
        'log_date' => now()->toDateString(),
        'duration_minutes' => 0,
        'hour_type' => HourType::Production->value,
    ], timeLogValidationRules());

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('duration_minutes'))->toBeTrue();
});

it('rejects a duration over 1440 minutes', function () {
    $validator = Validator::make([
        'log_date' => now()->toDateString(),
        'duration_minutes' => 1441,
        'hour_type' => HourType::Production->value,
    ], timeLogValidationRules());

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('duration_minutes'))->toBeTrue();
});

it('accepts a duration within bounds', function () {
    $validator = Validator::make([
        'log_date' => now()->toDateString(),
        'duration_minutes' => 60,
        'hour_type' => HourType::Production->value,
    ], timeLogValidationRules());

    expect($validator->fails())->toBeFalse();
});

it('rejects a log_date in the future', function () {
    $validator = Validator::make([
        'log_date' => now()->addDay()->toDateString(),
        'duration_minutes' => 60,
        'hour_type' => HourType::Production->value,
    ], timeLogValidationRules());

    expect($validator->fails())->toBeTrue()
        ->and($validator->errors()->has('log_date'))->toBeTrue();
});

it('prevents an employee from viewing another employee time log', function () {
    $owner = makeEmployee('owner@example.com');
    $other = makeEmployee('other@example.com');

    $log = TimeLog::create([
        'user_id' => $owner->id,
        'log_date' => now()->toDateString(),
        'hour_type' => HourType::Production,
        'duration_minutes' => 60,
        'entry_method' => EntryMethod::Manual,
    ]);

    expect($other->can('view', $log))->toBeFalse()
        ->and($owner->can('view', $log))->toBeTrue()
        ->and(TimeLog::query()->visibleTo($other)->count())->toBe(0);
});

it('prevents an employee from updating another employee time log', function () {
    Setting::query()->create([
        'key' => 'timelog_edit_window_hours',
        'value' => '48',
        'type' => 'int',
        'group' => 'timetracking',
        'label' => 'Edit Window',
    ]);

    $owner = makeEmployee('owner2@example.com');
    $other = makeEmployee('other2@example.com');

    $log = TimeLog::create([
        'user_id' => $owner->id,
        'log_date' => now()->toDateString(),
        'hour_type' => HourType::Production,
        'duration_minutes' => 60,
        'entry_method' => EntryMethod::Manual,
    ]);

    expect($other->can('update', $log))->toBeFalse();
});

it('blocks edits once the log is outside the configured edit window', function () {
    Setting::query()->create([
        'key' => 'timelog_edit_window_hours',
        'value' => '48',
        'type' => 'int',
        'group' => 'timetracking',
        'label' => 'Edit Window',
    ]);

    $owner = makeEmployee('window@example.com');

    $log = TimeLog::create([
        'user_id' => $owner->id,
        'log_date' => now()->toDateString(),
        'hour_type' => HourType::Production,
        'duration_minutes' => 60,
        'entry_method' => EntryMethod::Manual,
    ]);

    $log->forceFill(['created_at' => now()->subHours(72)])->save();

    expect($owner->can('update', $log))->toBeFalse();
});

it('stops the first timer when a second timer is started', function () {
    $user = makeEmployee('timer@example.com');
    $timerService = app(TimerService::class);

    $first = $timerService->start($user, null, null);
    expect($first->fresh()->ended_at)->toBeNull();

    $second = $timerService->start($user, null, null);

    expect($first->fresh()->ended_at)->not->toBeNull()
        ->and($second->fresh()->ended_at)->toBeNull()
        ->and($timerService->activeTimer($user)->is($second))->toBeTrue();
});

it('keeps task actual_hours in sync as production logs change', function () {
    $manager = User::factory()->create([
        'role_id' => Role::query()->firstOrCreate(['name' => 'manager'], ['label' => 'Manager'])->id,
    ]);
    $employee = makeEmployee('sync@example.com');

    $account = Account::create(['name' => 'Agility PR Solutions', 'code' => 'AGL-100']);

    $task = Task::create([
        'reference' => 'TSK-2026-9001',
        'title' => 'Weekly media report',
        'account_id' => $account->id,
        'assigned_to' => $employee->id,
        'created_by' => $manager->id,
        'complexity_tier' => ComplexityTier::Medium,
        'complexity_weight' => 3,
        'standard_hours' => '4.00',
        'status' => TaskStatus::InProgress,
    ]);

    $service = app(TimeLogService::class);

    $logOne = $service->createManualEntry($employee, [
        'log_date' => now()->toDateString(),
        'task_id' => $task->id,
        'account_id' => $account->id,
        'hour_type' => HourType::Production->value,
        'duration_minutes' => 120,
    ]);

    expect($task->fresh()->actual_hours)->toEqual('2.00');

    $service->createManualEntry($employee, [
        'log_date' => now()->toDateString(),
        'task_id' => $task->id,
        'account_id' => $account->id,
        'hour_type' => HourType::Production->value,
        'duration_minutes' => 60,
    ]);

    expect($task->fresh()->actual_hours)->toEqual('3.00');

    $service->updateEntry($logOne, [
        'log_date' => now()->toDateString(),
        'task_id' => $task->id,
        'account_id' => $account->id,
        'hour_type' => HourType::Production->value,
        'duration_minutes' => 30,
    ]);

    expect($task->fresh()->actual_hours)->toEqual('1.50');

    $service->deleteEntry($logOne);

    expect($task->fresh()->actual_hours)->toEqual('1.00');
});
