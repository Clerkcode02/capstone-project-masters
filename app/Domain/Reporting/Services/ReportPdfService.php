<?php

namespace App\Domain\Reporting\Services;

use App\Domain\Reporting\Enums\ReportType;
use App\Models\User;
use InvalidArgumentException;

/**
 * Resolves a report type + params into the Blade view, data, and filename
 * used both to render the queued PDF and, in tests, to verify the rendered
 * figures against the on-screen dashboard.
 */
class ReportPdfService
{
    public function __construct(
        private readonly MonthlyCapacityReportService $monthlyCapacity,
        private readonly QuarterlyCapacityReportService $quarterlyCapacity,
        private readonly TeamWorkloadReportService $teamWorkload,
        private readonly BottleneckReportService $bottleneck,
    ) {}

    /**
     * @param  array<string, mixed>  $params
     * @return array{view: string, data: array<string, mixed>, filename: string}
     */
    public function build(ReportType $type, array $params, User $actor): array
    {
        return match ($type) {
            ReportType::MonthlyCapacity => $this->buildMonthlyCapacity($params, $actor),
            ReportType::QuarterlyCapacity => $this->buildQuarterlyCapacity($params, $actor),
            ReportType::TeamWorkload => $this->buildTeamWorkload(),
            ReportType::Bottleneck => $this->buildBottleneck($params),
        };
    }

    /**
     * @param  array<string, mixed>  $params
     */
    private function buildMonthlyCapacity(array $params, User $actor): array
    {
        $year = (int) ($params['year'] ?? throw new InvalidArgumentException('year is required'));
        $month = (int) ($params['month'] ?? throw new InvalidArgumentException('month is required'));

        $data = $this->monthlyCapacity->build($year, $month, $actor);

        return [
            'view' => 'reports.pdf.monthly-capacity',
            'data' => $data,
            'filename' => "monthly-capacity-report-{$year}-{$month}.pdf",
        ];
    }

    /**
     * @param  array<string, mixed>  $params
     */
    private function buildQuarterlyCapacity(array $params, User $actor): array
    {
        $year = (int) ($params['year'] ?? throw new InvalidArgumentException('year is required'));
        $quarter = (int) ($params['quarter'] ?? throw new InvalidArgumentException('quarter is required'));

        $data = $this->quarterlyCapacity->build($year, $quarter, $actor);

        return [
            'view' => 'reports.pdf.quarterly-capacity',
            'data' => $data,
            'filename' => "quarterly-capacity-report-{$year}-Q{$quarter}.pdf",
        ];
    }

    private function buildTeamWorkload(): array
    {
        $data = $this->teamWorkload->build();

        return [
            'view' => 'reports.pdf.team-workload',
            'data' => $data,
            'filename' => 'team-workload-report-'.now()->format('Y-m-d').'.pdf',
        ];
    }

    /**
     * @param  array<string, mixed>  $params
     */
    private function buildBottleneck(array $params): array
    {
        $recommendations = $this->bottleneck->build(
            $params['from_date'] ?? null,
            $params['to_date'] ?? null,
        );

        return [
            'view' => 'reports.pdf.bottleneck',
            'data' => [
                'recommendations' => $recommendations,
                'fromDate' => $params['from_date'] ?? null,
                'toDate' => $params['to_date'] ?? null,
            ],
            'filename' => 'bottleneck-report-'.now()->format('Y-m-d').'.pdf',
        ];
    }
}
