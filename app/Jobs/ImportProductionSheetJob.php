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
use Illuminate\Support\Facades\Storage;

class ImportProductionSheetJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public readonly int $importedByUserId,
        public readonly string $storedPath,
        public readonly string $disk,
        public readonly string $originalFilename,
    ) {}

    public function handle(ProductionSheetImportService $service): void
    {
        $result = $service->commit($this->storedPath, $this->disk);

        Storage::disk($this->disk)->delete($this->storedPath);

        $importedBy = User::findOrFail($this->importedByUserId);

        ProductionSheetImported::dispatch(
            $importedBy,
            $this->originalFilename,
            $result['inserted'],
            $result['skipped'],
        );
    }
}
