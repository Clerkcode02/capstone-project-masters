<div class="space-y-6" wire:poll.30s>
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
        @if ($this->metric)
            <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                <p class="text-sm font-medium text-gray-500">This Month's Performance</p>
                <p class="mt-1 text-3xl font-semibold text-gray-900">{{ number_format((float) $this->metric->performance_percentage, 2) }}%</p>
                <x-tier-badge :tier="$this->metric->performance_tier" class="mt-3" />
            </div>
        @else
            <x-stat-card
                label="This Month's Performance"
                value="—"
                sublabel="Not yet calculated for this period."
            />
        @endif

        <x-stat-card
            label="Hours Logged This Month"
            value="{{ rtrim(rtrim(number_format($this->hoursLoggedThisMonth, 2), '0'), '.') }}h"
            sublabel="Production hours"
        />

        <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
            <p class="text-sm font-medium text-gray-500">Workload Score</p>
            <p class="mt-1 text-3xl font-semibold text-gray-900">{{ $this->workloadScore }}</p>
            <x-workload-bar :score="$this->workloadScore" :threshold="$this->workloadThreshold" class="mt-3" />
        </div>
    </div>

    @if ($this->metric)
        <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm text-sm text-gray-600">
            <p class="font-medium text-gray-700 mb-2">How this was calculated</p>
            <p>
                H_base {{ number_format((float) $this->metric->h_base, 2) }}h
                − H_leave {{ number_format((float) $this->metric->h_leave, 2) }}h
                = H_poss {{ number_format((float) $this->metric->h_poss, 2) }}h.
                H_poss × U_target ({{ number_format((float) $this->metric->u_target * 100, 1) }}%)
                = H_thresh {{ number_format((float) $this->metric->h_thresh, 2) }}h.
                H_prod {{ number_format((float) $this->metric->h_prod, 2) }}h
                ÷ H_thresh × 100 = {{ number_format((float) $this->metric->performance_percentage, 2) }}%.
            </p>
        </div>
    @endif

    <div class="rounded-lg border border-gray-200 bg-white shadow-sm">
        <div class="border-b border-gray-200 px-5 py-4">
            <h3 class="text-lg font-semibold text-gray-800">My Active Tasks</h3>
        </div>

        @if ($this->activeTasks->isEmpty())
            <div class="p-5">
                <x-empty-state message="No active tasks right now." />
            </div>
        @else
            <ul class="divide-y divide-gray-100">
                @foreach ($this->activeTasks as $task)
                    <li class="flex items-center justify-between gap-4 px-5 py-3">
                        <div>
                            <p class="font-medium text-gray-800">{{ $task->reference }} — {{ $task->title }}</p>
                            <p class="text-xs text-gray-500">
                                @if ($task->due_date)
                                    Due {{ $task->due_date->format('M j, Y') }}
                                @else
                                    No due date
                                @endif
                            </p>
                        </div>
                        <x-complexity-badge :tier="$task->complexity_tier" />
                    </li>
                @endforeach
            </ul>
        @endif
    </div>

    <livewire:time-tracking.timer-widget />
</div>
