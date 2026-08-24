<?php

use App\Models\Account;
use App\Models\Designation;
use App\Models\Role;
use App\Models\TimeLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;

uses(RefreshDatabase::class);

it('never lets an employee view another employee\'s time logs', function () {
    $employeeRole = Role::create(['name' => 'employee', 'label' => 'Employee']);
    $managerRole = Role::create(['name' => 'manager', 'label' => 'Manager']);
    $designation = Designation::create(['name' => 'Reports Analyst', 'utilization_target' => '0.800']);

    $manager = User::create([
        'first_name' => 'Maria', 'last_name' => 'Santos', 'email' => 'maria@example.com',
        'password' => 'secret', 'role_id' => $managerRole->id, 'designation_id' => $designation->id,
    ]);

    $owner = User::create([
        'employee_code' => 'EMP-0001', 'first_name' => 'Juan', 'last_name' => 'Dela Cruz',
        'email' => 'juan@example.com', 'password' => 'secret',
        'role_id' => $employeeRole->id, 'designation_id' => $designation->id,
    ]);

    $otherEmployee = User::create([
        'employee_code' => 'EMP-0002', 'first_name' => 'Ana', 'last_name' => 'Reyes',
        'email' => 'ana@example.com', 'password' => 'secret',
        'role_id' => $employeeRole->id, 'designation_id' => $designation->id,
    ]);

    $account = Account::create(['name' => 'Agility PR Solutions', 'code' => 'AGL-001']);

    $timeLog = TimeLog::create([
        'user_id' => $owner->id,
        'account_id' => $account->id,
        'log_date' => '2026-08-05',
        'hour_type' => 'production',
        'duration_minutes' => 120,
        'entry_method' => 'manual',
    ]);

    // Policy layer
    expect(Gate::forUser($owner)->allows('view', $timeLog))->toBeTrue();
    expect(Gate::forUser($otherEmployee)->denies('view', $timeLog))->toBeTrue();
    expect(Gate::forUser($manager)->allows('view', $timeLog))->toBeTrue();

    // Query-scope layer
    expect(TimeLog::query()->visibleTo($otherEmployee)->whereKey($timeLog->id)->exists())->toBeFalse();
    expect(TimeLog::query()->visibleTo($owner)->whereKey($timeLog->id)->exists())->toBeTrue();
});

it('restricts the production-sheet import action to managers and administrators', function () {
    $employeeRole = Role::create(['name' => 'employee', 'label' => 'Employee']);
    $managerRole = Role::create(['name' => 'manager', 'label' => 'Manager']);

    $employee = User::create([
        'first_name' => 'Juan', 'last_name' => 'Dela Cruz', 'email' => 'juan@example.com',
        'password' => 'secret', 'role_id' => $employeeRole->id,
    ]);

    $manager = User::create([
        'first_name' => 'Maria', 'last_name' => 'Santos', 'email' => 'maria@example.com',
        'password' => 'secret', 'role_id' => $managerRole->id,
    ]);

    expect(Gate::forUser($employee)->denies('import', TimeLog::class))->toBeTrue();
    expect(Gate::forUser($manager)->allows('import', TimeLog::class))->toBeTrue();
});
