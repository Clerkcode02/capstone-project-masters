<?php

namespace App\Livewire\Reporting;

use App\Domain\Identity\Enums\Role;
use App\Domain\Reporting\Enums\ReportType;
use App\Domain\Reporting\Exports\AuditLogExport;
use App\Domain\Reporting\Exports\BottleneckReportExport;
use App\Domain\Reporting\Exports\TeamWorkloadExport;
use App\Domain\Reporting\Exports\TimeLogExport;
use App\Domain\Reporting\Services\AuditLogReportService;
use App\Domain\Reporting\Services\BottleneckReportService;
use App\Domain\Reporting\Services\TeamWorkloadReportService;
use App\Domain\Reporting\Services\TimeLogReportService;
use App\Events\ReportExported;
use App\Jobs\GenerateReportPdfJob;
use App\Models\AuditLog;
use App\Models\CapacityMetric;
use App\Models\RedistributionRecommendation;
use App\Models\TimeLog;
use App\Notifications\ReportReady;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Maatwebsite\Excel\Excel as ExcelWriterType;
use Maatwebsite\Excel\Facades\Excel;

class ReportCenter extends Component
{
    public int $capacityYear;

    public int $capacityMonth;

    public int $quarterlyYear;

    public int $quarter;

    public ?string $timeLogFrom = null;

    public ?string $timeLogTo = null;

    public function mount(): void
    {
        $this->capacityYear = (int) now()->year;
        $this->capacityMonth = (int) now()->month;
        $this->quarterlyYear = (int) now()->year;
        $this->quarter = (int) ceil(now()->month / 3);
    }

    public function isAdmin(): bool
    {
        return auth()->user()->isRole(Role::Administrator);
    }

    public function isManagerOrAdmin(): bool
    {
        return auth()->user()->isRole(Role::Manager) || $this->isAdmin();
    }

    #[Computed]
    public function readyReports(): Collection
    {
        return auth()->user()->notifications()
            ->where('type', ReportReady::class)
            ->latest()
            ->limit(10)
            ->get();
    }

    public function queueMonthlyCapacityPdf(): void
    {
        $this->authorize('viewAny', CapacityMetric::class);

        GenerateReportPdfJob::dispatch(auth()->id(), ReportType::MonthlyCapacity, [
            'year' => $this->capacityYear,
            'month' => $this->capacityMonth,
        ]);

        session()->flash('status', 'Monthly capacity report queued. It will appear below once ready.');
    }

    public function queueQuarterlyCapacityPdf(): void
    {
        $this->authorize('viewAny', CapacityMetric::class);

        GenerateReportPdfJob::dispatch(auth()->id(), ReportType::QuarterlyCapacity, [
            'year' => $this->quarterlyYear,
            'quarter' => $this->quarter,
        ]);

        session()->flash('status', 'Quarterly capacity report queued. It will appear below once ready.');
    }

    public function queueWorkloadPdf(): void
    {
        $this->authorize('viewAny', CapacityMetric::class);

        GenerateReportPdfJob::dispatch(auth()->id(), ReportType::TeamWorkload, []);

        session()->flash('status', 'Team workload report queued. It will appear below once ready.');
    }

    public function queueBottleneckPdf(): void
    {
        $this->authorize('viewAny', RedistributionRecommendation::class);

        GenerateReportPdfJob::dispatch(auth()->id(), ReportType::Bottleneck, []);

        session()->flash('status', 'Bottleneck report queued. It will appear below once ready.');
    }

    public function downloadReady(string $notificationId)
    {
        $notification = auth()->user()->notifications()->whereKey($notificationId)->firstOrFail();

        $path = $notification->data['storage_path'];
        $filename = $notification->data['filename'];

        abort_unless(str_starts_with($path, 'reports/'.auth()->id().'/'), 403);

        $notification->markAsRead();

        return Storage::disk('local')->download($path, $filename);
    }

    public function downloadWorkloadExcel(TeamWorkloadReportService $service)
    {
        $this->authorize('viewAny', CapacityMetric::class);

        $data = $service->build();

        ReportExported::dispatch(auth()->user(), ReportType::TeamWorkload, 'excel');

        return Excel::download(
            new TeamWorkloadExport($data['scores'], $data['threshold']),
            'team-workload-report-'.now()->format('Y-m-d').'.xlsx',
        );
    }

    public function downloadBottleneckExcel(BottleneckReportService $service)
    {
        $this->authorize('viewAny', RedistributionRecommendation::class);

        $recommendations = $service->build();

        ReportExported::dispatch(auth()->user(), ReportType::Bottleneck, 'excel');

        return Excel::download(
            new BottleneckReportExport($recommendations),
            'bottleneck-report-'.now()->format('Y-m-d').'.xlsx',
        );
    }

    public function downloadTimeLogsExcel(TimeLogReportService $service)
    {
        $this->authorize('viewAny', TimeLog::class);

        $logs = $service->build(auth()->user(), $this->timeLogFrom, $this->timeLogTo);

        ReportExported::dispatch(auth()->user(), ReportType::TimeLog, 'excel');

        return Excel::download(
            new TimeLogExport($logs),
            'time-log-report-'.now()->format('Y-m-d').'.xlsx',
        );
    }

    public function downloadTimeLogsCsv(TimeLogReportService $service)
    {
        $this->authorize('viewAny', TimeLog::class);

        $logs = $service->build(auth()->user(), $this->timeLogFrom, $this->timeLogTo);

        ReportExported::dispatch(auth()->user(), ReportType::TimeLog, 'csv');

        return Excel::download(
            new TimeLogExport($logs),
            'time-log-report-'.now()->format('Y-m-d').'.csv',
            ExcelWriterType::CSV,
        );
    }

    public function downloadAuditLogsCsv(AuditLogReportService $service)
    {
        $this->authorize('viewAny', AuditLog::class);

        $logs = $service->build();

        ReportExported::dispatch(auth()->user(), ReportType::AuditLog, 'csv');

        return Excel::download(
            new AuditLogExport($logs),
            'audit-log-export-'.now()->format('Y-m-d').'.csv',
            ExcelWriterType::CSV,
        );
    }

    public function render()
    {
        return view('livewire.reporting.report-center');
    }
}
