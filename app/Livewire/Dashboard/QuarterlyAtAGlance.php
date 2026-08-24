<?php

namespace App\Livewire\Dashboard;

use App\Domain\Administration\Services\SettingsService;
use App\Domain\Capacity\Enums\PerformanceTier;
use App\Domain\Capacity\Services\PerformanceTierResolver;
use App\Models\CapacityMetric;
use App\Models\User;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;

class QuarterlyAtAGlance extends Component
{
    public int $year;

    public int $quarter;

    public function mount(): void
    {
        $this->authorize('viewAny', CapacityMetric::class);

        $this->year = (int) now()->year;
        $this->quarter = (int) ceil(now()->month / 3);
    }

    public function updatedYear(): void
    {
        unset($this->rows);
    }

    public function updatedQuarter(): void
    {
        unset($this->rows);
    }

    /**
     * The three calendar months that make up the selected quarter.
     *
     * @return array<int, int>
     */
    #[Computed]
    public function months(): array
    {
        $firstMonth = ($this->quarter - 1) * 3 + 1;

        return [$firstMonth, $firstMonth + 1, $firstMonth + 2];
    }

    /**
     * One row per employee: their three monthly metrics plus the quarterly
     * mean, computed on the fly from stored monthly capacity_metrics.
     *
     * @return Collection<int, array{user: User, months: array<int, ?CapacityMetric>, quarterly_percentage: ?float, quarterly_tier: PerformanceTier}>
     */
    #[Computed]
    public function rows(): Collection
    {
        $metrics = CapacityMetric::query()
            ->with(['user.designation'])
            ->where('period_type', 'monthly')
            ->where('period_year', $this->year)
            ->whereIn('period_month', $this->months)
            ->get()
            ->groupBy('user_id');

        if ($metrics->isEmpty()) {
            return collect();
        }

        $perfBelowMax = app(SettingsService::class)->decimal('perf_below_max', 90);
        $perfOverMin = app(SettingsService::class)->decimal('perf_over_min', 110);
        $resolver = app(PerformanceTierResolver::class);

        return $metrics
            ->map(function (Collection $userMetrics) use ($resolver, $perfBelowMax, $perfOverMin) {
                $user = $userMetrics->first()->user;

                $monthly = collect($this->months)->mapWithKeys(
                    fn (int $month) => [$month => $userMetrics->firstWhere('period_month', $month)]
                );

                $applicablePercentages = $monthly
                    ->filter(fn (?CapacityMetric $metric) => $metric && $metric->performance_tier !== PerformanceTier::NotApplicable)
                    ->map(fn (CapacityMetric $metric) => (float) $metric->performance_percentage);

                if ($applicablePercentages->isEmpty()) {
                    $quarterlyPercentage = null;
                    $quarterlyTier = PerformanceTier::NotApplicable;
                } else {
                    $quarterlyPercentage = round($applicablePercentages->avg(), 2);
                    $quarterlyTier = $resolver->tierForPercentage($quarterlyPercentage, $perfBelowMax, $perfOverMin);
                }

                return [
                    'user' => $user,
                    'months' => $monthly,
                    'quarterly_percentage' => $quarterlyPercentage,
                    'quarterly_tier' => $quarterlyTier,
                ];
            })
            ->sortBy(fn (array $row) => $row['user']?->full_name)
            ->values();
    }

    public function render()
    {
        return view('livewire.dashboard.quarterly-at-a-glance');
    }
}
