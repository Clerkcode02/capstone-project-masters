<?php

namespace Tests\Feature\TimeTracking;

use App\Domain\TimeTracking\Enums\EntryMethod;
use App\Domain\TimeTracking\Enums\HourType;
use App\Livewire\TimeTracking\ManualEntryForm;
use App\Livewire\TimeTracking\ProductionSheetImport;
use App\Livewire\TimeTracking\TimerWidget;
use App\Models\Account;
use App\Models\Role;
use App\Models\TimeLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Sprint 2 exit check: an employee can log a day's work three different
 * ways — timer, manual entry, and manager-driven production-sheet import —
 * and every method lands correctly in time_logs.
 */
class ThreeLoggingMethodsTest extends TestCase
{
    use RefreshDatabase;

    private function userWithRole(string $roleName, array $attributes = []): User
    {
        $role = Role::query()->firstOrCreate(
            ['name' => $roleName],
            ['label' => ucfirst($roleName)]
        );

        return User::factory()->create(array_merge(['role_id' => $role->id], $attributes));
    }

    public function test_timer_manual_entry_and_import_all_land_correctly_in_time_logs(): void
    {
        $employee = $this->userWithRole('employee', ['employee_code' => 'EMP-VALID1']);
        $manager = $this->userWithRole('manager');
        $account = Account::create(['name' => 'News Monitoring', 'code' => 'ACC-VALID']);

        // 1. Timer: start then stop.
        $timer = Livewire::actingAs($employee)
            ->test(TimerWidget::class)
            ->set('accountId', $account->id)
            ->call('start');

        $timerLogId = $timer->get('activeTimeLogId');
        $this->assertNotNull($timerLogId);

        Livewire::actingAs($employee)
            ->test(TimerWidget::class)
            ->set('activeTimeLogId', $timerLogId)
            ->call('stop');

        $timerLog = TimeLog::query()->findOrFail($timerLogId);
        $this->assertSame($employee->id, $timerLog->user_id);
        $this->assertSame(EntryMethod::Timer, $timerLog->entry_method);
        $this->assertNotNull($timerLog->ended_at);
        $this->assertGreaterThanOrEqual(1, $timerLog->duration_minutes);

        // 2. Manual entry.
        Livewire::actingAs($employee)
            ->test(ManualEntryForm::class)
            ->set('accountId', $account->id)
            ->set('logDate', now()->toDateString())
            ->set('hourType', HourType::Production->value)
            ->set('durationMinutes', 90)
            ->set('notes', 'Manual entry for exit check')
            ->call('save');

        $this->assertDatabaseHas('time_logs', [
            'user_id' => $employee->id,
            'entry_method' => EntryMethod::Manual->value,
            'duration_minutes' => 90,
            'notes' => 'Manual entry for exit check',
        ]);

        // 3. Production-sheet import (manager-driven, queue runs synchronously in tests).
        Storage::fake('local');

        $csv = "employee_code,log_date,account_code,hour_type,duration_minutes,notes\n"
            ."EMP-VALID1,2026-08-15,ACC-VALID,production,300,Imported row for exit check\n";

        $file = UploadedFile::fake()->createWithContent('sheet.csv', $csv);

        Livewire::actingAs($manager)
            ->test(ProductionSheetImport::class)
            ->set('sheet', $file)
            ->call('upload')
            ->call('confirm');

        $this->assertDatabaseHas('time_logs', [
            'user_id' => $employee->id,
            'entry_method' => EntryMethod::Import->value,
            'duration_minutes' => 300,
            'log_date' => '2026-08-15',
        ]);

        // All three entry methods are present for this employee.
        $methods = TimeLog::query()
            ->where('user_id', $employee->id)
            ->pluck('entry_method')
            ->map(fn (EntryMethod $method) => $method->value)
            ->unique()
            ->sort()
            ->values()
            ->all();

        $this->assertSame(['import', 'manual', 'timer'], $methods);
    }
}
