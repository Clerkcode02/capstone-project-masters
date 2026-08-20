<?php

namespace App\Livewire\TimeTracking;

use App\Domain\TimeTracking\Enums\HourType;
use App\Domain\TimeTracking\Services\TimeLogService;
use App\Models\Account;
use App\Models\Task;
use App\Models\TimeLog;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Validate;
use Livewire\Component;

class ManualEntryForm extends Component
{
    #[Validate('required|date|before_or_equal:today')]
    public string $log_date = '';

    #[Validate('nullable|integer|exists:tasks,id')]
    public ?int $task_id = null;

    #[Validate('nullable|integer|exists:accounts,id')]
    public ?int $account_id = null;

    #[Validate('required|string')]
    public string $hour_type = 'production';

    #[Validate('required|integer|min:1|max:1440')]
    public ?int $duration_minutes = null;

    #[Validate('nullable|string|max:255')]
    public string $notes = '';

    public function mount(): void
    {
        $this->authorize('create', TimeLog::class);
        $this->log_date = now()->toDateString();
    }

    #[Computed]
    public function tasks()
    {
        return Task::query()->visibleTo(auth()->user())->active()->orderBy('title')->get();
    }

    #[Computed]
    public function accounts()
    {
        return Account::query()->whereHas('users', fn ($q) => $q->whereKey(auth()->id()))->orderBy('name')->get();
    }

    #[Computed]
    public function hourTypes(): array
    {
        return HourType::cases();
    }

    public function save(TimeLogService $timeLogService): void
    {
        $this->authorize('create', TimeLog::class);

        $data = $this->validate();

        $timeLogService->createManualEntry(auth()->user(), $data);

        $this->reset(['task_id', 'account_id', 'duration_minutes', 'notes']);
        $this->log_date = now()->toDateString();

        $this->dispatch('time-log-saved');
        session()->flash('status', __('Time log saved.'));
    }

    public function render(): View
    {
        return view('livewire.time-tracking.manual-entry-form');
    }
}
