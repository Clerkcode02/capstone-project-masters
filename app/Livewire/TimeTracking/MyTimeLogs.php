<?php

namespace App\Livewire\TimeTracking;

use App\Domain\TimeTracking\Enums\HourType;
use App\Domain\TimeTracking\Services\TimeLogService;
use App\Models\Account;
use App\Models\Task;
use App\Models\TimeLog;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;

class MyTimeLogs extends Component
{
    public string $month;

    public ?int $editingId = null;

    public string $edit_log_date = '';

    public ?int $edit_task_id = null;

    public ?int $edit_account_id = null;

    public string $edit_hour_type = 'production';

    public ?int $edit_duration_minutes = null;

    public string $edit_notes = '';

    public function mount(): void
    {
        $this->month = now()->format('Y-m');
    }

    #[Computed]
    public function logs()
    {
        $period = Carbon::createFromFormat('Y-m', $this->month);

        return TimeLog::query()
            ->visibleTo(auth()->user())
            ->whereBetween('log_date', [$period->copy()->startOfMonth(), $period->copy()->endOfMonth()])
            ->with('task', 'account')
            ->orderByDesc('log_date')
            ->get();
    }

    #[Computed]
    public function totalHours(): float
    {
        return round($this->logs->sum('duration_minutes') / 60, 2);
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

    public function canEdit(TimeLog $log, TimeLogService $timeLogService): bool
    {
        return auth()->user()->can('update', $log) && $timeLogService->canEdit($log);
    }

    public function edit(int $logId): void
    {
        $log = TimeLog::query()->findOrFail($logId);
        $this->authorize('update', $log);

        $this->editingId = $log->id;
        $this->edit_log_date = $log->log_date->toDateString();
        $this->edit_task_id = $log->task_id;
        $this->edit_account_id = $log->account_id;
        $this->edit_hour_type = $log->hour_type->value;
        $this->edit_duration_minutes = $log->duration_minutes;
        $this->edit_notes = (string) $log->notes;
    }

    public function cancelEdit(): void
    {
        $this->editingId = null;
    }

    public function update(TimeLogService $timeLogService): void
    {
        $log = TimeLog::query()->findOrFail($this->editingId);
        $this->authorize('update', $log);

        $data = $this->validate([
            'edit_log_date' => ['required', 'date', 'before_or_equal:today'],
            'edit_task_id' => ['nullable', 'integer', 'exists:tasks,id'],
            'edit_account_id' => ['nullable', 'integer', 'exists:accounts,id'],
            'edit_hour_type' => ['required', 'string'],
            'edit_duration_minutes' => ['required', 'integer', 'min:1', 'max:1440'],
            'edit_notes' => ['nullable', 'string', 'max:255'],
        ]);

        $timeLogService->updateEntry($log, [
            'log_date' => $data['edit_log_date'],
            'task_id' => $data['edit_task_id'],
            'account_id' => $data['edit_account_id'],
            'hour_type' => $data['edit_hour_type'],
            'duration_minutes' => $data['edit_duration_minutes'],
            'notes' => $data['edit_notes'],
        ]);

        $this->editingId = null;
        unset($this->logs);
    }

    public function delete(int $logId, TimeLogService $timeLogService): void
    {
        $log = TimeLog::query()->findOrFail($logId);
        $this->authorize('delete', $log);

        $timeLogService->deleteEntry($log);

        unset($this->logs);
    }

    #[On('time-log-saved')]
    public function refreshLogs(): void
    {
        unset($this->logs);
    }

    public function render(): View
    {
        return view('livewire.time-tracking.my-time-logs');
    }
}
