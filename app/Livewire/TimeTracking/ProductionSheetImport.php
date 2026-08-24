<?php

namespace App\Livewire\TimeTracking;

use App\Domain\TimeTracking\DTOs\ProductionSheetRowResult;
use App\Domain\TimeTracking\Services\ProductionSheetImportService;
use App\Jobs\ImportProductionSheetJob;
use App\Models\TimeLog;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithFileUploads;

class ProductionSheetImport extends Component
{
    use WithFileUploads;

    public const DISK = 'imports';

    public $sheet = null;

    public ?string $storedPath = null;

    public ?string $originalFilename = null;

    public bool $previewed = false;

    /** @var array<int, array{row: int, data: array<string, mixed>}> */
    public array $validRows = [];

    /** @var array<int, array{row: int, data: array<string, mixed>, reasons: array<int, string>}> */
    public array $invalidRows = [];

    public ?string $queuedMessage = null;

    public function mount(): void
    {
        $this->authorize('import', TimeLog::class);
    }

    public function uploadAndPreview(ProductionSheetImportService $service): void
    {
        $this->authorize('import', TimeLog::class);
        $this->queuedMessage = null;

        $this->validate([
            'sheet' => 'required|file|mimes:csv,txt,xlsx|max:5120',
        ]);

        $this->originalFilename = $this->sheet->getClientOriginalName();

        $filename = Str::random(40).'.'.$this->sheet->getClientOriginalExtension();
        $this->storedPath = $this->sheet->storeAs('', $filename, self::DISK);

        $results = $service->preview($this->storedPath, self::DISK);

        $this->validRows = $results
            ->filter(fn (ProductionSheetRowResult $r) => $r->isValid)
            ->map(fn (ProductionSheetRowResult $r) => ['row' => $r->rowNumber, 'data' => $r->attributes])
            ->values()
            ->all();

        $this->invalidRows = $results
            ->filter(fn (ProductionSheetRowResult $r) => ! $r->isValid)
            ->map(fn (ProductionSheetRowResult $r) => ['row' => $r->rowNumber, 'data' => $r->raw, 'reasons' => $r->reasons])
            ->values()
            ->all();

        $this->previewed = true;
    }

    public function confirmImport(): void
    {
        $this->authorize('import', TimeLog::class);

        if (! $this->storedPath || $this->validRows === []) {
            return;
        }

        ImportProductionSheetJob::dispatch(
            auth()->id(),
            $this->storedPath,
            self::DISK,
            $this->originalFilename ?? 'production-sheet',
        );

        $this->queuedMessage = 'Import queued: '.count($this->validRows).' row(s) will be inserted, '.count($this->invalidRows).' row(s) skipped.';

        $this->resetPreview(deleteFile: false);
    }

    public function cancel(): void
    {
        $this->resetPreview(deleteFile: true);
    }

    private function resetPreview(bool $deleteFile): void
    {
        if ($deleteFile && $this->storedPath) {
            Storage::disk(self::DISK)->delete($this->storedPath);
        }

        $this->reset(['sheet', 'storedPath', 'originalFilename', 'previewed', 'validRows', 'invalidRows']);
    }

    public function render()
    {
        return view('livewire.time-tracking.production-sheet-import');
    }
}
