<?php

namespace App\Domain\TimeTracking\Services;

use App\Domain\TimeTracking\Imports\ProductionSheetImport;
use Maatwebsite\Excel\Facades\Excel;

class ProductionSheetParser
{
    /**
     * Parses the sheet and returns raw rows keyed by their 1-indexed file row
     * number (accounting for the heading row), so preview and rejection
     * messages can point back at the exact spreadsheet row.
     *
     * @return array<int, array<string, mixed>>
     */
    public function parse(string $path, string $disk): array
    {
        $sheets = Excel::toArray(new ProductionSheetImport, $path, $disk);
        $rows = $sheets[0] ?? [];

        $result = [];
        foreach ($rows as $index => $row) {
            $result[$index + 2] = $row;
        }

        return $result;
    }
}
