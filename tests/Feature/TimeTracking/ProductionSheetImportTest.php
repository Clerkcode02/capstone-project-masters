<?php

namespace Tests\Feature\TimeTracking;

use App\Domain\TimeTracking\Enums\EntryMethod;
use App\Domain\TimeTracking\Enums\HourType;
use App\Domain\TimeTracking\Services\ProductionSheetImportService;
use App\Jobs\ImportProductionSheetJob;
use App\Livewire\TimeTracking\ProductionSheetImport;
use App\Models\Account;
use App\Models\Role;
use App\Models\TimeLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class ProductionSheetImportTest extends TestCase
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

    private function fixturePath(): string
    {
        return base_path('tests/Fixtures/time-tracking/production-sheet-mixed.csv');
    }

    public function test_uploading_a_mixed_sheet_produces_a_preview_with_valid_and_rejected_rows(): void
    {
        Storage::fake('local');

        $manager = $this->userWithRole('manager');
        $this->userWithRole('employee', ['employee_code' => 'EMP-VALID1']);
        $employee2 = $this->userWithRole('employee', ['employee_code' => 'EMP-VALID2']);
        $account = Account::create(['name' => 'News Monitoring', 'code' => 'ACC-VALID']);

        // Pre-existing log so the fixture's "duplicate of existing" row is rejected.
        TimeLog::create([
            'user_id' => $employee2->id,
            'account_id' => $account->id,
            'log_date' => '2026-08-20',
            'hour_type' => HourType::Leave->value,
            'duration_minutes' => 480,
            'entry_method' => EntryMethod::Import->value,
        ]);

        $file = UploadedFile::fake()->createWithContent(
            'production-sheet-mixed.csv',
            file_get_contents($this->fixturePath())
        );

        $component = Livewire::actingAs($manager)
            ->test(ProductionSheetImport::class)
            ->set('sheet', $file)
            ->call('upload');

        $component->assertSet('previewed', true);

        $valid = $component->get('validRows');
        $rejected = $component->get('rejectedRows');

        $this->assertCount(2, $valid);
        $this->assertCount(8, $rejected);

        // Preview only — nothing written to time_logs yet beyond the seeded duplicate.
        $this->assertSame(1, TimeLog::query()->count());

        fwrite(STDOUT, "\n--- Production sheet preview ---\n");
        fwrite(STDOUT, 'Valid: '.json_encode($valid, JSON_PRETTY_PRINT)."\n");
        fwrite(STDOUT, 'Rejected: '.json_encode($rejected, JSON_PRETTY_PRINT)."\n");
    }

    public function test_confirming_the_import_queues_a_job_that_inserts_the_valid_rows(): void
    {
        Storage::fake('local');
        Queue::fake();

        $manager = $this->userWithRole('manager');
        $this->userWithRole('employee', ['employee_code' => 'EMP-VALID1']);
        $employee2 = $this->userWithRole('employee', ['employee_code' => 'EMP-VALID2']);
        $account = Account::create(['name' => 'News Monitoring', 'code' => 'ACC-VALID']);

        TimeLog::create([
            'user_id' => $employee2->id,
            'account_id' => $account->id,
            'log_date' => '2026-08-20',
            'hour_type' => HourType::Leave->value,
            'duration_minutes' => 480,
            'entry_method' => EntryMethod::Import->value,
        ]);

        $file = UploadedFile::fake()->createWithContent(
            'production-sheet-mixed.csv',
            file_get_contents($this->fixturePath())
        );

        Livewire::actingAs($manager)
            ->test(ProductionSheetImport::class)
            ->set('sheet', $file)
            ->call('upload')
            ->call('confirm');

        Queue::assertPushed(ImportProductionSheetJob::class, fn (ImportProductionSheetJob $job) => count($job->validRows) === 2
            && $job->importedByUserId === $manager->id
        );
    }

    public function test_the_import_service_commit_inserts_valid_rows_with_import_entry_method(): void
    {
        $manager = $this->userWithRole('manager');
        $employee = $this->userWithRole('employee', ['employee_code' => 'EMP-VALID1']);
        $account = Account::create(['name' => 'News Monitoring', 'code' => 'ACC-VALID']);

        $service = app(ProductionSheetImportService::class);

        $count = $service->commit([
            [
                'user_id' => $employee->id,
                'account_id' => $account->id,
                'log_date' => '2026-08-18',
                'hour_type' => HourType::Production->value,
                'duration_minutes' => 420,
                'notes' => 'Imported row',
            ],
        ], $manager);

        $this->assertSame(1, $count);
        $this->assertDatabaseHas('time_logs', [
            'user_id' => $employee->id,
            'account_id' => $account->id,
            'log_date' => '2026-08-18',
            'hour_type' => HourType::Production->value,
            'duration_minutes' => 420,
            'entry_method' => EntryMethod::Import->value,
        ]);
    }

    public function test_a_non_manager_cannot_access_the_import_screen(): void
    {
        $employee = $this->userWithRole('employee');

        $response = $this->actingAs($employee)->get(route('manager.production-sheet-import'));

        $response->assertForbidden();
    }
}
