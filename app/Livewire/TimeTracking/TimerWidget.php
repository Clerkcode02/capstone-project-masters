<?php

namespace App\Livewire\TimeTracking;

use App\Domain\TimeTracking\Enums\HourType;
use App\Domain\TimeTracking\Services\TimerService;
use App\Models\Account;
use App\Models\Task;
use App\Models\TimeLog;
use DomainException;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Computed;
use Livewire\Component;

class TimerWidget extends Component
{
    public ?int $account_id = null;

    public ?int $task_id = null;

    public string $hour_type = 'production';

    public string $notes = '';

    public function mount(): void
    {
        $this->authorize('create', TimeLog::class);
    }

    #[Computed]
    public function active(): ?array
    {
        return app(TimerService::class)->active(auth()->user());
    }

    #[Computed]
    public function accounts()
    {
        return auth()->user()->accounts()->orderBy('name')->get();
    }

    #[Computed]
    public function tasks()
    {
        if (! $this->account_id) {
            return collect();
        }

        return auth()->user()->assignedTasks()
            ->where('account_id', $this->account_id)
            ->orderBy('title')
            ->get();
    }

    public function start(TimerService $timer): void
    {
        $this->authorize('create', TimeLog::class);

        $this->validate([
            'account_id' => 'required|integer|exists:accounts,id',
            'task_id' => 'nullable|integer|exists:tasks,id',
            'hour_type' => 'required|string|in:production,non_production,leave',
        ]);

        try {
            $timer->start(
                auth()->user(),
                Account::findOrFail($this->account_id),
                $this->task_id ? Task::findOrFail($this->task_id) : null,
                HourType::from($this->hour_type),
            );
        } catch (DomainException $e) {
            $this->addError('timer', $e->getMessage());

            return;
        }

        unset($this->active);
    }

    public function stop(TimerService $timer): void
    {
        $this->authorize('create', TimeLog::class);

        try {
            $timer->stop(auth()->user(), $this->notes !== '' ? $this->notes : null);
        } catch (DomainException $e) {
            $this->addError('timer', $e->getMessage());

            return;
        }

        $this->reset(['account_id', 'task_id', 'notes']);
        $this->hour_type = 'production';
        unset($this->active);

        $this->dispatch('time-log-created');
    }

    public function getElapsedLabelProperty(): ?string
    {
        $active = $this->active;

        if (! $active) {
            return null;
        }

        $minutes = Carbon::parse($active['started_at'])->diffInMinutes(now());

        return sprintf('%02d:%02d', intdiv($minutes, 60), $minutes % 60);
    }

    public function render()
    {
        return view('livewire.time-tracking.timer-widget');
    }
}
