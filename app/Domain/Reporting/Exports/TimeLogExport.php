<?php

namespace App\Domain\Reporting\Exports;

use App\Models\TimeLog;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class TimeLogExport implements FromCollection, WithHeadings, WithMapping
{
    public function __construct(private readonly Collection $timeLogs) {}

    public function collection(): Collection
    {
        return $this->timeLogs;
    }

    public function headings(): array
    {
        return [
            'Employee', 'Date', 'Task Reference', 'Account', 'Hour Type',
            'Duration (Hours)', 'Entry Method', 'Notes',
        ];
    }

    public function map($timeLog): array
    {
        /** @var TimeLog $timeLog */
        return [
            $timeLog->user?->full_name,
            $timeLog->log_date->format('Y-m-d'),
            $timeLog->task?->reference ?? '—',
            $timeLog->account?->name ?? '—',
            $timeLog->hour_type->label(),
            round($timeLog->duration_minutes / 60, 2),
            $timeLog->entry_method->label(),
            $timeLog->notes,
        ];
    }
}
