<?php

use App\Domain\TimeTracking\Enums\EntryMethod;
use App\Domain\TimeTracking\Enums\HourType;
use App\Domain\TimeTracking\Services\ManualTimeEntryService;
use App\Domain\TimeTracking\Services\ProductionSheetImportService;
use App\Domain\TimeTracking\Services\TimerService;
use App\Jobs\ImportProductionSheetJob;
use App\Models\Account;
use App\Models\AuditLog;
use App\Models\Designation;
use App\Models\Role;
use App\Models\Task;
use App\Models\TimeLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

function makeEmployee(): User
{
    $role = Role::firstOrCreate(['name' => 'employee'], ['label' => 'Employee']);
    $designation = Designation::firstOrCreate(['name' => 'Reports Analyst'], ['utilization_target' => '0.800']);

    return User::create([
        'employee_code' => 'EMP-0001',
        'first_name' => 'Juan',
        'last_name' => 'Dela Cruz',
        'email' => 'juan.delacruz@example.com',
        'password' => 'secret',
        'role_id' => $role->id,
        'designation_id' => $designation->id,
        'is_active' => true,
    ]);
}

it('logs a day of work via the timer', function () {
    $employee = makeEmployee();
    $account = Account::create(['name' => 'Agility PR Solutions', 'code' => 'AGL-001']);

    $timer = app(TimerService::class);
    $timer->start($employee, $account, null, HourType::Production);

    expect($timer->active($employee))->not->toBeNull();

    $timeLog = $timer->stop($employee, 'Clipping report');

    expect($timeLog->entry_method)->toBe(EntryMethod::Timer)
        ->and($timeLog->user_id)->toBe($employee->id)
        ->and($timeLog->account_id)->toBe($account->id)
        ->and($timeLog->duration_minutes)->toBeGreaterThanOrEqual(1);

    expect(TimeLog::where('entry_method', EntryMethod::Timer->value)->count())->toBe(1);
    expect($timer->active($employee))->toBeNull();
});

it('logs a day of work via manual entry', function () {
    $employee = makeEmployee();
    $account = Account::create(['name' => 'Agility PR Solutions', 'code' => 'AGL-001']);
    $task = Task::create([
        'reference' => 'TSK-2026-0001',
        'title' => 'Weekly media clipping report',
        'account_id' => $account->id,
        'assigned_to' => $employee->id,
        'created_by' => $employee->id,
        'complexity_tier' => 'medium',
        'complexity_weight' => 3,
        'standard_hours' => '4.00',
    ]);

    $timeLog = app(ManualTimeEntryService::class)->log($employee, [
        'account_id' => $account->id,
        'task_id' => $task->id,
        'log_date' => '2026-08-05',
        'hour_type' => HourType::Production->value,
        'duration_minutes' => 240,
        'notes' => 'Logged manually',
    ]);

    expect($timeLog->entry_method)->toBe(EntryMethod::Manual)
        ->and($timeLog->task_id)->toBe($task->id);

    expect(TimeLog::where('entry_method', EntryMethod::Manual->value)->count())->toBe(1);
});

it('logs a day of work via production-sheet import', function () {
    Storage::fake('imports');

    $employee = makeEmployee();
    $account = Account::create(['name' => 'Agility PR Solutions', 'code' => 'AGL-001']);

    $csv = "employee_code,log_date,account_code,hour_type,hours,notes\n".
        "EMP-0001,2026-08-06,AGL-001,production,4,Imported row\n";

    Storage::disk('imports')->put('import-test.csv', $csv);

    (new ImportProductionSheetJob($employee->id, 'import-test.csv', 'imports', 'import-test.csv'))
        ->handle(app(ProductionSheetImportService::class));

    $timeLog = TimeLog::where('entry_method', EntryMethod::Import->value)->first();

    expect($timeLog)->not->toBeNull()
        ->and($timeLog->user_id)->toBe($employee->id)
        ->and($timeLog->account_id)->toBe($account->id)
        ->and($timeLog->duration_minutes)->toBe(240);

    Storage::disk('imports')->assertMissing('import-test.csv');

    expect(AuditLog::where('action', 'production_sheet_imported')->count())->toBe(1);
});
