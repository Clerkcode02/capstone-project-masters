<?php

namespace App\Livewire\TimeTracking;

use App\Domain\TimeTracking\Services\ProductionSheetImportService;
use App\Jobs\ImportProductionSheetJob;
use App\Models\TimeLog;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithFileUploads;

class ProductionSheetImport extends Component
{
    use WithFileUploads;

    public $sheet;

    public array $validRows = [];

    public array $rejectedRows = [];

    public ?string $storedPath = null;

    public bool $previewed = false;

    public function mount(): void
    {
        $this->authorize('import', TimeLog::class);
    }

    public function upload(ProductionSheetImportService $service): void
    {
        $this->authorize('import', TimeLog::class);

        $this->validate([
            'sheet' => ['required', 'file', 'mimes:csv,txt,xlsx', 'max:5120'],
        ]);

        $randomName = Str::random(40).'.'.$this->sheet->getClientOriginalExtension();
        $storedPath = $this->sheet->storeAs('imports', $randomName, 'local');

        $result = $service->parse($storedPath);

        $this->validRows = $result['valid'];
        $this->rejectedRows = $result['rejected'];
        $this->storedPath = $storedPath;
        $this->previewed = true;
        $this->sheet = null;
    }

    public function confirm(): void
    {
        $this->authorize('import', TimeLog::class);

        abort_unless($this->previewed && $this->storedPath && $this->validRows !== [], 400);

        ImportProductionSheetJob::dispatch($this->validRows, auth()->id(), $this->storedPath);

        session()->flash('status', count($this->validRows).' row(s) queued for import.');

        $this->reset(['sheet', 'validRows', 'rejectedRows', 'storedPath', 'previewed']);
    }

    public function cancel(): void
    {
        $this->reset(['sheet', 'validRows', 'rejectedRows', 'storedPath', 'previewed']);
    }

    public function render()
    {
        return view('livewire.time-tracking.production-sheet-import');
    }
}
