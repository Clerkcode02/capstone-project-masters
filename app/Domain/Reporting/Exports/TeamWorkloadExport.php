<?php

namespace App\Domain\Reporting\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class TeamWorkloadExport implements FromCollection, WithHeadings, WithMapping
{
    public function __construct(
        private readonly Collection $scores,
        private readonly int $threshold,
    ) {}

    public function collection(): Collection
    {
        return $this->scores;
    }

    public function headings(): array
    {
        return ['Employee', 'Designation', 'Workload Score', 'Workload Threshold', 'Over Threshold'];
    }

    /**
     * @param  array{user_id: int, name: string, designation: ?string, score: int}  $row
     */
    public function map($row): array
    {
        return [
            $row['name'],
            $row['designation'] ?? '—',
            $row['score'],
            $this->threshold,
            $row['score'] > $this->threshold ? 'Yes' : 'No',
        ];
    }
}
