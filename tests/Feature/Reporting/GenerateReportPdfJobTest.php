<?php

use App\Domain\Reporting\Enums\ReportType;
use App\Domain\Reporting\Services\ReportPdfService;
use App\Jobs\GenerateReportPdfJob;
use App\Models\AuditLog;
use App\Models\Designation;
use App\Models\Role;
use App\Models\User;
use App\Notifications\ReportReady;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

it('generates the team workload PDF, stores it privately for the requester, and notifies them', function () {
    Storage::fake('local');

    $role = Role::firstOrCreate(['name' => 'manager'], ['label' => 'Manager']);
    $designation = Designation::create(['name' => 'Reports Analyst', 'utilization_target' => '0.800']);
    $manager = User::create([
        'first_name' => 'Test',
        'last_name' => 'Manager',
        'email' => 'manager@example.com',
        'password' => 'secret',
        'role_id' => $role->id,
        'designation_id' => $designation->id,
        'is_active' => true,
    ]);

    (new GenerateReportPdfJob($manager->id, ReportType::TeamWorkload, []))->handle(app(ReportPdfService::class));

    $files = Storage::disk('local')->allFiles('reports/'.$manager->id);
    expect($files)->toHaveCount(1);

    $manager->refresh();
    expect($manager->notifications()->where('type', ReportReady::class)->count())->toBe(1);

    expect(AuditLog::where('action', 'report_exported')->count())->toBe(1);
});
