<?php

namespace App\Livewire\Dashboard;

use App\Domain\Administration\Services\SettingsService;
use App\Domain\Tasks\Services\WorkloadScoreService;
use App\Models\CapacityMetric;
use Illuminate\Support\Collection;
use Livewire\Attributes\Computed;
use Livewire\Component;

class TeamWorkloadChart extends Component
{
    public function mount(): void
    {
        $this->authorize('viewAny', CapacityMetric::class);
    }

    /**
     * Employees with their workload score, sorted descending (highest
     * workload first) as required by the horizontal bar chart.
     *
     * @return Collection<int, array{user_id: int, name: string, designation: ?string, score: int}>
     */
    #[Computed]
    public function scores(): Collection
    {
        return app(WorkloadScoreService::class)
            ->scoresForActiveUsers()
            ->map(fn (array $entry) => [
                'user_id' => $entry['user']->id,
                'name' => $entry['user']->full_name,
                'designation' => $entry['user']->designation?->name,
                'score' => $entry['score'],
            ])
            ->sortByDesc('score')
            ->values();
    }

    #[Computed]
    public function threshold(): int
    {
        return app(SettingsService::class)->int('workload_threshold', 12);
    }

    public function render()
    {
        return view('livewire.dashboard.team-workload-chart');
    }
}
