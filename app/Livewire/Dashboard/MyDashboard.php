<?php

namespace App\Livewire\Dashboard;

use App\Domain\Administration\Services\SettingsService;
use App\Domain\Tasks\Enums\TaskStatus;
use App\Domain\Tasks\Services\WorkloadScoreService;
use App\Domain\TimeTracking\Enums\HourType;
use App\Models\CapacityMetric;
use App\Models\Task;
use App\Models\TimeLog;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;

/**
 * The employee's own workspace: own performance, own active tasks, own
 * workload score, own hours logged. No teammate data ever appears here —
 * every query below is filtered to auth()->id(), not scoped by role.
 */
class MyDashboard extends Component
{
    public int $year;

    public int $month;

    public function mount(): void
    {
        $this->year = (int) now()->year;
        $this->month = (int) now()->month;
    }

    #[Computed]
    public function metric(): ?CapacityMetric
    {
        return CapacityMetric::query()
            ->where('user_id', auth()->id())
            ->where('period_type', 'monthly')
            ->where('period_year', $this->year)
            ->where('period_month', $this->month)
            ->first();
    }

    #[Computed]
    public function activeTasks(): Collection
    {
        return Task::query()
            ->where('assigned_to', auth()->id())
            ->where('status', TaskStatus::InProgress->value)
            ->orderBy('due_date')
            ->get();
    }

    #[Computed]
    public function workloadScore(): int
    {
        return app(WorkloadScoreService::class)->scoreForUser(auth()->user());
    }

    #[Computed]
    public function workloadThreshold(): float
    {
        return app(SettingsService::class)->decimal('workload_threshold', 12);
    }

    #[Computed]
    public function hoursLoggedThisMonth(): float
    {
        $start = now()->setDate($this->year, $this->month, 1)->startOfDay();
        $end = (clone $start)->endOfMonth()->endOfDay();

        $minutes = TimeLog::query()
            ->where('user_id', auth()->id())
            ->where('hour_type', HourType::Production->value)
            ->whereBetween('log_date', [$start, $end])
            ->sum('duration_minutes');

        return round($minutes / 60, 2);
    }

    public function render()
    {
        return view('livewire.dashboard.my-dashboard');
    }
}
