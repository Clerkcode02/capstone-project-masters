<?php

namespace App\Livewire\Dashboard;

use App\Domain\Capacity\Enums\PerformanceTier;
use App\Domain\Capacity\Services\CapacityCalculationService;
use App\Models\CapacityMetric;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;
use RuntimeException;

class MonthlyAtAGlance extends Component
{
    public int $year;

    public int $month;

    public ?int $expandedUserId = null;

    public bool $recalculating = false;

    public function mount(): void
    {
        $this->authorize('viewAny', CapacityMetric::class);

        $this->year = (int) now()->year;
        $this->month = (int) now()->month;
    }

    public function updatedYear(): void
    {
        $this->expandedUserId = null;
        unset($this->metrics);
    }

    public function updatedMonth(): void
    {
        $this->expandedUserId = null;
        unset($this->metrics);
    }

    #[Computed]
    public function metrics(): Collection
    {
        return CapacityMetric::query()
            ->with(['user.designation'])
            ->where('period_type', 'monthly')
            ->where('period_year', $this->year)
            ->where('period_month', $this->month)
            ->get()
            ->sortBy(fn (CapacityMetric $metric) => $metric->user?->full_name)
            ->values();
    }

    #[Computed]
    public function teamSize(): int
    {
        return $this->metrics->count();
    }

    #[Computed]
    public function onTargetCount(): int
    {
        return $this->metrics->where('performance_tier', PerformanceTier::Acceptable)->count();
    }

    #[Computed]
    public function overUtilizedCount(): int
    {
        return $this->metrics->where('performance_tier', PerformanceTier::Over)->count();
    }

    #[Computed]
    public function underUtilizedCount(): int
    {
        return $this->metrics->where('performance_tier', PerformanceTier::Below)->count();
    }

    public function toggleDrilldown(int $userId): void
    {
        $this->expandedUserId = $this->expandedUserId === $userId ? null : $userId;
    }

    public function recalculate(CapacityCalculationService $service): void
    {
        $this->authorize('recalculate', CapacityMetric::class);

        $this->recalculating = true;

        try {
            $service->recalculateForMonth($this->year, $this->month);
        } catch (RuntimeException $e) {
            $this->recalculating = false;
            session()->flash('error', $e->getMessage());

            return;
        }

        $this->recalculating = false;
        unset($this->metrics);
        session()->flash('status', 'Capacity metrics recalculated for '.now()->setDate($this->year, $this->month, 1)->format('F Y').'.');
    }

    public function render()
    {
        return view('livewire.dashboard.monthly-at-a-glance');
    }
}
