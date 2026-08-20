<?php

namespace App\Livewire\TimeTracking;

use App\Domain\TimeTracking\Enums\HourType;
use App\Domain\TimeTracking\Services\ManualTimeEntryService;
use App\Models\Account;
use App\Models\TimeLog;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;
use Livewire\Component;

class ManualEntryForm extends Component
{
    public ?int $accountId = null;

    public ?int $taskId = null;

    public string $logDate = '';

    public string $hourType = 'production';

    public ?int $durationMinutes = null;

    public string $notes = '';

    public function mount(): void
    {
        $this->authorize('create', TimeLog::class);

        $this->logDate = now()->toDateString();
    }

    protected function rules(): array
    {
        return [
            'accountId' => ['nullable', 'integer', 'exists:accounts,id'],
            'taskId' => ['nullable', 'integer', 'exists:tasks,id'],
            'logDate' => ['required', 'date', 'before_or_equal:today'],
            'hourType' => ['required', Rule::enum(HourType::class)],
            'durationMinutes' => ['required', 'integer', 'min:1', 'max:1440'],
            'notes' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function save(ManualTimeEntryService $service): void
    {
        $this->authorize('create', TimeLog::class);

        $validated = $this->validate();

        $service->create(auth()->user(), [
            'task_id' => $validated['taskId'],
            'account_id' => $validated['accountId'],
            'log_date' => $validated['logDate'],
            'hour_type' => $validated['hourType'],
            'duration_minutes' => $validated['durationMinutes'],
            'notes' => $validated['notes'] ?: null,
        ]);

        $this->reset(['accountId', 'taskId', 'durationMinutes', 'notes']);
        $this->logDate = now()->toDateString();

        session()->flash('status', 'Time log saved.');
    }

    public function accounts(): Collection
    {
        return Account::query()->where('is_active', true)->orderBy('name')->get();
    }

    public function hourTypes(): array
    {
        return HourType::cases();
    }

    public function render()
    {
        return view('livewire.time-tracking.manual-entry-form', [
            'accounts' => $this->accounts(),
            'hourTypes' => $this->hourTypes(),
        ]);
    }
}
