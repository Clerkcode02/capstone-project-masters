<?php

namespace App\Domain\TimeTracking\Imports;

use Maatwebsite\Excel\Concerns\Import;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class ProductionSheetImport implements Import, SkipsEmptyRows, WithHeadingRow
{
    public function headingRow(): int
    {
        return 1;
    }
}
