<?php

namespace App\Livewire\TimeTracking;

use App\Domain\TimeTracking\Enums\EntryMethod;
use App\Domain\TimeTracking\Services\TimerService;
use App\Models\Account;
use App\Models\TimeLog;
use Illuminate\Support\Collection;
use Livewire\Component;

class TimerWidget extends Component
{
    public ?int $activeTimeLogId = null;

    public ?int $accountId = null;

    public ?int $taskId = null;

    public function mount(): void
    {
        $this->authorize('create', TimeLog::class);

        $active = TimeLog::query()
            ->where('user_id', auth()->id())
            ->where('entry_method', EntryMethod::Timer->value)
            ->whereNull('ended_at')
            ->latest('started_at')
            ->first();

        $this->activeTimeLogId = $active?->id;
        $this->accountId = $active?->account_id;
        $this->taskId = $active?->task_id;
    }

    public function start(TimerService $service): void
    {
        $this->authorize('create', TimeLog::class);

        $this->validate([
            'accountId' => ['required', 'integer', 'exists:accounts,id'],
            'taskId' => ['nullable', 'integer', 'exists:tasks,id'],
        ]);

        $timeLog = $service->start(auth()->user(), $this->accountId, $this->taskId);

        $this->activeTimeLogId = $timeLog->id;
    }

    public function stop(TimerService $service): void
    {
        $this->authorize('create', TimeLog::class);

        if (! $this->activeTimeLogId) {
            return;
        }

        $timeLog = TimeLog::query()->findOrFail($this->activeTimeLogId);
        $service->stop($timeLog, auth()->user());

        $this->reset(['activeTimeLogId', 'accountId', 'taskId']);
    }

    public function accounts(): Collection
    {
        return Account::query()->where('is_active', true)->orderBy('name')->get();
    }

    public function render()
    {
        return view('livewire.time-tracking.timer-widget', [
            'accounts' => $this->accounts(),
        ]);
    }
}
