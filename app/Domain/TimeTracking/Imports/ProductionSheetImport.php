<?php

namespace App\Domain\TimeTracking\Imports;

use Maatwebsite\Excel\Concerns\ToArray;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class ProductionSheetImport implements ToArray, WithHeadingRow
{
    private array $rows = [];

    public function array(array $array): void
    {
        $this->rows = $array;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function rows(): array
    {
        return $this->rows;
    }
}
