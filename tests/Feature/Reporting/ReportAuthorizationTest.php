<?php

use App\Domain\Reporting\Enums\ReportType;
use App\Jobs\GenerateReportPdfJob;
use App\Livewire\Reporting\ReportCenter;
use App\Models\Designation;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function reportingUser(string $roleName, string $email, ?Designation $designation = null): User
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

it('forbids an employee from queueing the monthly capacity PDF', function () {
    $employee = reportingUser('employee', 'employee@example.com');

    Livewire::actingAs($employee)
        ->test(ReportCenter::class)
        ->call('queueMonthlyCapacityPdf')
        ->assertForbidden();
});

it('lets a manager queue the monthly capacity PDF on the database queue', function () {
    Queue::fake();

    $designation = Designation::create(['name' => 'Reports Analyst', 'utilization_target' => '0.800']);
    $manager = reportingUser('manager', 'manager@example.com', $designation);

    Livewire::actingAs($manager)
        ->test(ReportCenter::class)
        ->call('queueMonthlyCapacityPdf')
        ->assertHasNoErrors();

    Queue::assertPushed(GenerateReportPdfJob::class, function (GenerateReportPdfJob $job) use ($manager) {
        return $job->requestedByUserId === $manager->id
            && $job->reportType === ReportType::MonthlyCapacity;
    });
});

it('forbids an employee from reaching the bottleneck report', function () {
    $employee = reportingUser('employee', 'employee@example.com');

    Livewire::actingAs($employee)
        ->test(ReportCenter::class)
        ->call('queueBottleneckPdf')
        ->assertForbidden();
});

it('forbids a manager from exporting the audit log — administrator only', function () {
    $designation = Designation::create(['name' => 'Reports Analyst', 'utilization_target' => '0.800']);
    $manager = reportingUser('manager', 'manager@example.com', $designation);

    Livewire::actingAs($manager)
        ->test(ReportCenter::class)
        ->call('downloadAuditLogsCsv')
        ->assertForbidden();
});

it('lets an administrator export the audit log', function () {
    $admin = reportingUser('administrator', 'admin@example.com');

    Livewire::actingAs($admin)
        ->test(ReportCenter::class)
        ->call('downloadAuditLogsCsv')
        ->assertHasNoErrors();
});

it('lets an employee download their own time log export without a policy denial', function () {
    $employee = reportingUser('employee', 'employee@example.com');

    Livewire::actingAs($employee)
        ->test(ReportCenter::class)
        ->call('downloadTimeLogsExcel')
        ->assertHasNoErrors();
});
