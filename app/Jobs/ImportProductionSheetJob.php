<?php

namespace App\Jobs;

use App\Domain\TimeTracking\Services\ProductionSheetImportService;
use App\Events\ProductionSheetImported;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ImportProductionSheetJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * @param  array<int, array<string, mixed>>  $validRows
     */
    public function __construct(
        public readonly array $validRows,
        public readonly int $importedByUserId,
        public readonly string $storedPath,
    ) {}

    public function handle(ProductionSheetImportService $service): void
    {
        $importer = User::findOrFail($this->importedByUserId);
        $count = $service->commit($this->validRows, $importer);

        ProductionSheetImported::dispatch($this->importedByUserId, $this->storedPath, $count);
    }
}
