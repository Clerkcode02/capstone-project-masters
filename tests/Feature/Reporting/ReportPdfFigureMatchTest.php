<?php

use App\Domain\Reporting\Enums\ReportType;
use App\Domain\Reporting\Services\ReportPdfService;
use App\Livewire\Dashboard\MonthlyAtAGlance;
use App\Models\CapacityMetric;
use App\Models\Designation;
use App\Models\MonthlyBaseline;
use App\Models\Role;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function pdfMatchSettings(): void
{
    Setting::create(['key' => 'perf_below_max', 'value' => '90', 'type' => 'decimal', 'group' => 'capacity', 'label' => 'Below max']);
    Setting::create(['key' => 'perf_over_min', 'value' => '110', 'type' => 'decimal', 'group' => 'capacity', 'label' => 'Over min']);
}

function pdfMatchUser(string $roleName, string $email, ?Designation $designation = null): User
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

it('renders the monthly capacity PDF with the exact figures shown on the Monthly At-a-Glance dashboard', function () {
    pdfMatchSettings();
    MonthlyBaseline::create(['period_year' => now()->year, 'period_month' => now()->month, 'baseline_hours' => 160]);

    $designation = Designation::create(['name' => 'Reports Analyst', 'utilization_target' => '0.800']);
    $manager = pdfMatchUser('manager', 'manager@example.com', $designation);
    $analyst = pdfMatchUser('employee', 'analyst@example.com', $designation);

    CapacityMetric::create([
        'user_id' => $analyst->id,
        'period_type' => 'monthly',
        'period_year' => now()->year,
        'period_month' => now()->month,
        'h_base' => 160,
        'h_leave' => 16,
        'h_poss' => 144,
        'u_target' => '0.800',
        'h_thresh' => 115.20,
        'h_prod' => 100.80,
        'h_non_prod' => 0,
        'performance_percentage' => 87.50,
        'performance_tier' => 'below',
        'effective_availability_hours' => 14.40,
        'computed_at' => now(),
    ]);

    // What the dashboard shows on screen.
    $dashboardHtml = Livewire::actingAs($manager)
        ->test(MonthlyAtAGlance::class)
        ->html();

    // What the PDF export would render, built from the same underlying data.
    ['view' => $view, 'data' => $data] = app(ReportPdfService::class)->build(
        ReportType::MonthlyCapacity,
        ['year' => now()->year, 'month' => now()->month],
        $manager,
    );
    $pdfHtml = view($view, $data)->render();

    // The dollar figures and tier that matter for the capstone's formula-verification claim.
    foreach (['100.80', '115.20', '87.50', 'Below Target'] as $figure) {
        expect($dashboardHtml)->toContain($figure);
        expect($pdfHtml)->toContain($figure);
    }
});
