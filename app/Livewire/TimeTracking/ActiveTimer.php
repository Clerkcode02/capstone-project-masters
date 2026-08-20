<?php

namespace App\Livewire\TimeTracking;

use App\Domain\TimeTracking\Services\TimerService;
use App\Models\Account;
use App\Models\Task;
use App\Models\TimeLog;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Computed;
use Livewire\Component;

class ActiveTimer extends Component
{
    public ?int $taskId = null;

    public ?int $accountId = null;

    public function mount(): void
    {
        $this->authorize('create', TimeLog::class);
    }

    #[Computed]
    public function activeTimer(): ?TimeLog
    {
        return app(TimerService::class)->activeTimer(auth()->user());
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

    public function start(TimerService $timerService): void
    {
        $this->authorize('create', TimeLog::class);

        $timerService->start(auth()->user(), $this->taskId, $this->accountId);

        unset($this->activeTimer);
        $this->taskId = null;
        $this->accountId = null;
    }

    public function stop(TimerService $timerService): void
    {
        $timer = $timerService->activeTimer(auth()->user());

        if ($timer) {
            $this->authorize('update', $timer);
        }

        $timerService->stop(auth()->user());

        unset($this->activeTimer);
    }

    public function render(): View
    {
        return view('livewire.time-tracking.active-timer');
    }
}
