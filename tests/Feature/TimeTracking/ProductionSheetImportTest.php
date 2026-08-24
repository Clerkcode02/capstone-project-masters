<?php

use App\Domain\TimeTracking\Enums\EntryMethod;
use App\Domain\TimeTracking\Enums\HourType;
use App\Domain\TimeTracking\Services\ProductionSheetImportService;
use App\Models\Account;
use App\Models\Designation;
use App\Models\Role;
use App\Models\TimeLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

function seedImportFixtures(): array
{
    $role = Role::firstOrCreate(['name' => 'employee'], ['label' => 'Employee']);
    $designation = Designation::firstOrCreate(['name' => 'Reports Analyst'], ['utilization_target' => '0.800']);

    $employee = User::create([
        'employee_code' => 'EMP-0001',
        'first_name' => 'Juan',
        'last_name' => 'Dela Cruz',
        'email' => 'juan.delacruz@example.com',
        'password' => 'secret',
        'role_id' => $role->id,
        'designation_id' => $designation->id,
        'is_active' => true,
    ]);

    $account = Account::create(['name' => 'Agility PR Solutions', 'code' => 'AGL-001']);

    return [$employee, $account];
}

it('separates valid rows from rejected rows with reasons in the preview', function () {
    Storage::fake('imports');
    [$employee, $account] = seedImportFixtures();

    $csv = implode("\n", [
        'employee_code,log_date,account_code,hour_type,hours,notes',
        'EMP-0001,2026-08-03,AGL-001,production,4.5,Valid row',
        'EMP-9999,2026-08-03,AGL-001,production,2,Unknown employee',
        'EMP-0001,2026-08-03,BAD-999,production,2,Unknown account',
        'EMP-0001,2026-08-03,AGL-001,vacation,2,Bad hour type',
        'EMP-0001,2026-08-03,AGL-001,production,0,Zero hours',
    ]);

    Storage::disk('imports')->put('sheet.csv', $csv);

    $results = app(ProductionSheetImportService::class)->preview('sheet.csv', 'imports');

    expect($results)->toHaveCount(5);

    $valid = $results->filter->isValid;
    $invalid = $results->reject->isValid;

    expect($valid)->toHaveCount(1)
        ->and($invalid)->toHaveCount(4);

    expect($invalid->firstWhere('rowNumber', 3)->reasons)->toContain("employee_code 'EMP-9999' does not match an active employee.");
    expect($invalid->firstWhere('rowNumber', 4)->reasons)->toContain("account_code 'BAD-999' does not match an active account.");
    expect($invalid->firstWhere('rowNumber', 5)->reasons[0])->toContain('hour_type must be one of');
    expect($invalid->firstWhere('rowNumber', 6)->reasons)->toContain('hours must be greater than 0.');
});

it('rejects a row that duplicates another row in the same file', function () {
    Storage::fake('imports');
    seedImportFixtures();

    $csv = implode("\n", [
        'employee_code,log_date,account_code,hour_type,hours,notes',
        'EMP-0001,2026-08-03,AGL-001,production,4,First',
        'EMP-0001,2026-08-03,AGL-001,production,3,Same employee/date/account/hour_type',
    ]);

    Storage::disk('imports')->put('sheet.csv', $csv);

    $results = app(ProductionSheetImportService::class)->preview('sheet.csv', 'imports');

    expect($results->filter->isValid)->toHaveCount(1);
    expect($results->reject->isValid->first()->reasons)->toContain(
        'duplicate row: another row in this file already has the same employee, date, account, and hour type.'
    );
});

it('rejects a row that duplicates an existing time log', function () {
    Storage::fake('imports');
    [$employee, $account] = seedImportFixtures();

    TimeLog::create([
        'user_id' => $employee->id,
        'account_id' => $account->id,
        'log_date' => '2026-08-03',
        'hour_type' => HourType::Production,
        'duration_minutes' => 120,
        'entry_method' => EntryMethod::Manual,
    ]);

    Storage::disk('imports')->put('sheet.csv', implode("\n", [
        'employee_code,log_date,account_code,hour_type,hours,notes',
        'EMP-0001,2026-08-03,AGL-001,production,4,Duplicate of existing log',
    ]));

    $results = app(ProductionSheetImportService::class)->preview('sheet.csv', 'imports');

    expect($results->filter->isValid)->toHaveCount(0);
    expect($results->first()->reasons)->toContain(
        'a time log already exists for this employee, date, account, and hour type.'
    );
});

it('inserts only the valid rows inside a transaction on commit', function () {
    Storage::fake('imports');
    seedImportFixtures();

    Storage::disk('imports')->put('sheet.csv', implode("\n", [
        'employee_code,log_date,account_code,hour_type,hours,notes',
        'EMP-0001,2026-08-03,AGL-001,production,4,Valid',
        'EMP-0001,2026-08-03,AGL-001,vacation,2,Invalid hour type',
    ]));

    $result = app(ProductionSheetImportService::class)->commit('sheet.csv', 'imports');

    expect($result)->toBe(['inserted' => 1, 'skipped' => 1]);
    expect(TimeLog::count())->toBe(1);
    expect(TimeLog::first()->entry_method)->toBe(EntryMethod::Import);
});
