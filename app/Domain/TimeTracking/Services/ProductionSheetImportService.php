<?php

namespace App\Domain\TimeTracking\Services;

use App\Domain\TimeTracking\DTOs\ProductionSheetRowResult;
use App\Models\TimeLog;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ProductionSheetImportService
{
    public function __construct(
        private readonly ProductionSheetParser $parser,
    ) {}

    /**
     * Parses and validates the sheet without writing anything, for the
     * preview screen.
     *
     * @return Collection<int, ProductionSheetRowResult>
     */
    public function preview(string $path, string $disk): Collection
    {
        $rows = $this->parser->parse($path, $disk);
        $validator = new ProductionSheetRowValidator;

        return collect($rows)
            ->map(fn (array $row, int $rowNumber) => $validator->validate($rowNumber, $row))
            ->values();
    }

    /**
     * Re-validates the sheet (guarding against state that changed between
     * preview and confirm) and inserts every valid row in one transaction.
     *
     * @return array{inserted: int, skipped: int}
     */
    public function commit(string $path, string $disk): array
    {
        $results = $this->preview($path, $disk);
        $valid = $results->filter(fn (ProductionSheetRowResult $result) => $result->isValid);

        DB::transaction(function () use ($valid) {
            foreach ($valid as $result) {
                TimeLog::create($result->attributes);
            }
        });

        return [
            'inserted' => $valid->count(),
            'skipped' => $results->count() - $valid->count(),
        ];
    }
}
