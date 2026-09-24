<?php

use App\Domain\Reporting\Services\TimeLogReportService;
use App\Models\Designation;
use App\Models\Role;
use App\Models\TimeLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function timeLogReportUser(string $roleName, string $email, Designation $designation): User
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

it('never includes another employee\'s time logs in an employee\'s export, even when a user_id filter is passed', function () {
    $designation = Designation::create(['name' => 'Reports Analyst', 'utilization_target' => '0.800']);
    $employee = timeLogReportUser('employee', 'employee@example.com', $designation);
    $colleague = timeLogReportUser('employee', 'colleague@example.com', $designation);

    TimeLog::create([
        'user_id' => $employee->id,
        'log_date' => now()->toDateString(),
        'hour_type' => 'production',
        'duration_minutes' => 60,
        'entry_method' => 'manual',
    ]);

    TimeLog::create([
        'user_id' => $colleague->id,
        'log_date' => now()->toDateString(),
        'hour_type' => 'production',
        'duration_minutes' => 120,
        'entry_method' => 'manual',
    ]);

    $logs = app(TimeLogReportService::class)->build($employee, null, null, $colleague->id);

    expect($logs)->toHaveCount(1);
    expect($logs->first()->user_id)->toBe($employee->id);
});

it('lets a manager export logs across employees', function () {
    $designation = Designation::create(['name' => 'Reports Analyst', 'utilization_target' => '0.800']);
    $manager = timeLogReportUser('manager', 'manager@example.com', $designation);
    $employeeOne = timeLogReportUser('employee', 'one@example.com', $designation);
    $employeeTwo = timeLogReportUser('employee', 'two@example.com', $designation);

    TimeLog::create(['user_id' => $employeeOne->id, 'log_date' => now()->toDateString(), 'hour_type' => 'production', 'duration_minutes' => 60, 'entry_method' => 'manual']);
    TimeLog::create(['user_id' => $employeeTwo->id, 'log_date' => now()->toDateString(), 'hour_type' => 'production', 'duration_minutes' => 90, 'entry_method' => 'manual']);

    $logs = app(TimeLogReportService::class)->build($manager);

    expect($logs)->toHaveCount(2);
});
