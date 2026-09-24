<?php

namespace App\Domain\Reporting\Exports;

use App\Models\RedistributionRecommendation;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class BottleneckReportExport implements FromCollection, WithHeadings, WithMapping
{
    public function __construct(private readonly Collection $recommendations) {}

    public function collection(): Collection
    {
        return $this->recommendations;
    }

    public function headings(): array
    {
        return [
            'Task Reference', 'Task Title', 'Account', 'From', 'Suggested Assignee',
            'Actual Hours', 'Historical Avg Hours', 'Variance %', 'Basis', 'Status',
            'Reason', 'Created At',
        ];
    }

    public function map($recommendation): array
    {
        /** @var RedistributionRecommendation $recommendation */
        return [
            $recommendation->task?->reference,
            $recommendation->task?->title,
            $recommendation->task?->account?->name,
            $recommendation->fromUser?->full_name,
            $recommendation->suggestedUser?->full_name ?? '—',
            (float) $recommendation->actual_hours,
            $recommendation->historical_avg_hours !== null ? (float) $recommendation->historical_avg_hours : '—',
            $recommendation->variance_percentage !== null ? (float) $recommendation->variance_percentage : '—',
            $recommendation->basis,
            $recommendation->status->label(),
            $recommendation->reason,
            $recommendation->created_at->format('Y-m-d H:i'),
        ];
    }
}
