<div wire:poll.30s>
    @if (session('status'))
        <div class="mb-4 rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
            {{ session('status') }}
        </div>
    @endif

    @if (session('error'))
        <div class="mb-4 rounded-md border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
            {{ session('error') }}
        </div>
    @endif

    <div class="flex flex-wrap items-end justify-between gap-4">
        <div class="flex items-end gap-3">
            <div>
                <label for="month" class="block text-xs font-medium text-gray-500">Month</label>
                <select id="month" wire:model.live="month" class="mt-1 rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    @foreach (range(1, 12) as $m)
                        <option value="{{ $m }}">{{ \Carbon\Carbon::create()->month($m)->format('F') }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="year" class="block text-xs font-medium text-gray-500">Year</label>
                <select id="year" wire:model.live="year" class="mt-1 rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    @foreach (range(now()->year - 1, now()->year + 1) as $y)
                        <option value="{{ $y }}">{{ $y }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        @can('recalculate', \App\Models\CapacityMetric::class)
            <button
                type="button"
                wire:click="recalculate"
                wire:loading.attr="disabled"
                wire:target="recalculate"
                class="inline-flex items-center gap-2 rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-indigo-500 disabled:cursor-not-allowed disabled:opacity-60"
            >
                <svg wire:loading wire:target="recalculate" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                </svg>
                <span wire:loading.remove wire:target="recalculate">Recalculate</span>
                <span wire:loading wire:target="recalculate">Recalculating&hellip;</span>
            </button>
        @endcan
    </div>

    <div class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <x-stat-card label="Team Size" :value="$this->teamSize" sublabel="employees with metrics this period" />
        <x-stat-card label="On Target" :value="$this->onTargetCount" tone="acceptable" sublabel="performing within the acceptable band" />
        <x-stat-card label="Over-Utilized" :value="$this->overUtilizedCount" tone="over" sublabel="burnout risk — review workload" />
        <x-stat-card label="Under-Utilized" :value="$this->underUtilizedCount" tone="below" sublabel="below the production threshold" />
    </div>

    <div class="mt-6 overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
        @if ($this->metrics->isEmpty())
            <x-empty-state message="No capacity metrics for this period yet. Use Recalculate to generate them from logged time." />
        @else
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Employee</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Designation</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">H_prod / H_thresh</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Tier</th>
                        <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Remaining Capacity</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @foreach ($this->metrics as $metric)
                        <tr
                            wire:click="toggleDrilldown({{ $metric->user_id }})"
                            class="cursor-pointer hover:bg-gray-50 {{ $expandedUserId === $metric->user_id ? 'bg-indigo-50/40' : '' }}"
                        >
                            <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ $metric->user?->full_name ?? 'Unknown' }}</td>
                            <td class="px-4 py-3 text-sm text-gray-500">{{ $metric->user?->designation?->name ?? '—' }}</td>
                            <td class="px-4 py-3 text-sm text-gray-700">{{ number_format((float) $metric->h_prod, 2) }}h / {{ number_format((float) $metric->h_thresh, 2) }}h</td>
                            <td class="px-4 py-3"><x-tier-badge :tier="$metric->performance_tier" :percentage="$metric->performance_percentage" /></td>
                            <td class="px-4 py-3">
                                <x-workload-bar
                                    :score="$metric->h_prod"
                                    :threshold="$metric->h_thresh"
                                    label="Hours used"
                                    class="w-48"
                                />
                            </td>
                        </tr>
                        @if ($expandedUserId === $metric->user_id)
                            <tr>
                                <td colspan="5" class="bg-gray-50 px-4 py-4">
                                    <p class="mb-3 text-xs font-semibold uppercase tracking-wider text-gray-500">Formula drill-down</p>
                                    <div class="grid grid-cols-2 gap-x-8 gap-y-2 text-sm sm:grid-cols-3 lg:grid-cols-6">
                                        <div>
                                            <p class="text-xs text-gray-400">H_base</p>
                                            <p class="font-mono font-medium text-gray-900">{{ number_format((float) $metric->h_base, 2) }}h</p>
                                        </div>
                                        <div>
                                            <p class="text-xs text-gray-400">H_leave</p>
                                            <p class="font-mono font-medium text-gray-900">{{ number_format((float) $metric->h_leave, 2) }}h</p>
                                        </div>
                                        <div>
                                            <p class="text-xs text-gray-400">H_poss = H_base − H_leave</p>
                                            <p class="font-mono font-medium text-gray-900">{{ number_format((float) $metric->h_poss, 2) }}h</p>
                                        </div>
                                        <div>
                                            <p class="text-xs text-gray-400">U_target</p>
                                            <p class="font-mono font-medium text-gray-900">{{ number_format((float) $metric->u_target, 3) }}</p>
                                        </div>
                                        <div>
                                            <p class="text-xs text-gray-400">H_thresh = H_poss × U_target</p>
                                            <p class="font-mono font-medium text-gray-900">{{ number_format((float) $metric->h_thresh, 2) }}h</p>
                                        </div>
                                        <div>
                                            <p class="text-xs text-gray-400">H_prod</p>
                                            <p class="font-mono font-medium text-gray-900">{{ number_format((float) $metric->h_prod, 2) }}h</p>
                                        </div>
                                    </div>
                                    <p class="mt-3 font-mono text-sm text-gray-700">
                                        Performance % = (H_prod ÷ H_thresh) × 100 =
                                        @if (is_null($metric->performance_percentage))
                                            n/a (H_thresh = 0, full-month leave)
                                        @else
                                            ({{ number_format((float) $metric->h_prod, 2) }} ÷ {{ number_format((float) $metric->h_thresh, 2) }}) × 100 = {{ number_format((float) $metric->performance_percentage, 2) }}%
                                        @endif
                                    </p>
                                    <p class="mt-1 font-mono text-sm text-gray-700">
                                        Effective Availability = H_thresh − H_prod = {{ number_format((float) $metric->effective_availability_hours, 2) }}h
                                    </p>
                                    <p class="mt-2 text-xs text-gray-400">Computed {{ $metric->computed_at?->diffForHumans() }}</p>
                                </td>
                            </tr>
                        @endif
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
</div>
