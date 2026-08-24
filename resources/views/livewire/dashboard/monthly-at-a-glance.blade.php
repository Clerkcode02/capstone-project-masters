<div wire:poll.30s class="rounded-lg border border-board-line bg-board-ink p-4 shadow-[0_8px_24px_-8px_rgba(0,0,0,0.5)] sm:p-6">
    @if (session('status'))
        <div class="mb-4 flex items-center gap-2.5 rounded-sm border border-board-line bg-board-panel px-4 py-2.5 font-board text-sm text-board-text">
            <span class="flex h-5 w-5 shrink-0 items-center justify-center rounded-sm bg-board-lit text-board-panel">
                <svg viewBox="0 0 16 16" class="h-3 w-3" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M3.5 8.5 6.5 11.5 12.5 4.5" /></svg>
            </span>
            {{ session('status') }}
        </div>
    @endif

    @if (session('error'))
        <div class="mb-4 flex items-center gap-2.5 rounded-sm border border-board-line bg-board-panel px-4 py-2.5 font-board text-sm text-board-red">
            <span class="flex h-5 w-5 shrink-0 items-center justify-center rounded-sm bg-board-red text-board-red-ink">
                <svg viewBox="0 0 16 16" class="h-3 w-3" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><path d="M8 5v4M8 11.25v.01" /></svg>
            </span>
            {{ session('error') }}
        </div>
    @endif

    <div class="flex flex-wrap items-end justify-between gap-4 border-b border-board-line pb-4">
        <div class="flex items-end gap-3">
            <div>
                <label for="month" class="block font-board text-[0.65rem] font-semibold uppercase tracking-[0.1em] text-board-muted">Month</label>
                <select id="month" wire:model.live="month" class="mt-1 rounded-sm border-board-line bg-board-panel py-1.5 pl-2.5 pr-8 font-board text-sm text-board-text focus:border-board-amber focus:ring-board-amber">
                    @foreach (range(1, 12) as $m)
                        <option value="{{ $m }}">{{ \Carbon\Carbon::create()->month($m)->format('F') }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="year" class="block font-board text-[0.65rem] font-semibold uppercase tracking-[0.1em] text-board-muted">Year</label>
                <select id="year" wire:model.live="year" class="mt-1 rounded-sm border-board-line bg-board-panel py-1.5 pl-2.5 pr-8 font-board text-sm text-board-text focus:border-board-amber focus:ring-board-amber">
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
                class="inline-flex items-center gap-2 rounded-sm border border-board-amber bg-board-amber px-4 py-2 font-board text-sm font-semibold uppercase tracking-[0.04em] text-board-amber-ink shadow-sm transition hover:bg-board-amber/90 focus:outline-none focus:ring-2 focus:ring-board-amber focus:ring-offset-2 focus:ring-offset-board-ink disabled:cursor-not-allowed disabled:opacity-60"
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

    <div class="mt-4 flex flex-col divide-y divide-board-line rounded-sm border border-board-line bg-board-panel sm:flex-row sm:divide-x sm:divide-y-0">
        <x-stat-card class="sm:flex-1" label="Team Size" :value="$this->teamSize" sublabel="employees with metrics this period" />
        <x-stat-card class="sm:flex-1" label="On Target" :value="$this->onTargetCount" tone="acceptable" sublabel="performing within the acceptable band" />
        <x-stat-card class="sm:flex-1" label="Over Threshold" :value="$this->overUtilizedCount" tone="over" sublabel="burnout risk — review workload" />
        <x-stat-card class="sm:flex-1" label="Below Threshold" :value="$this->underUtilizedCount" tone="below" sublabel="under the production threshold" />
    </div>

    <div class="mt-4 overflow-hidden rounded-sm border border-board-line">
        @if ($this->metrics->isEmpty())
            <x-empty-state message="No capacity metrics for this period yet. Use Recalculate to generate them from logged time." />
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-board-line">
                    <thead class="bg-board-panel">
                        <tr>
                            <th scope="col" class="px-4 py-2.5 text-left font-board text-[0.65rem] font-semibold uppercase tracking-[0.1em] text-board-muted">Employee</th>
                            <th scope="col" class="hidden px-4 py-2.5 text-left font-board text-[0.65rem] font-semibold uppercase tracking-[0.1em] text-board-muted md:table-cell">Designation</th>
                            <th scope="col" class="hidden px-4 py-2.5 text-left font-board text-[0.65rem] font-semibold uppercase tracking-[0.1em] text-board-muted sm:table-cell">H_prod / H_thresh</th>
                            <th scope="col" class="px-4 py-2.5 text-left font-board text-[0.65rem] font-semibold uppercase tracking-[0.1em] text-board-muted">Status</th>
                            <th scope="col" class="px-4 py-2.5 text-left font-board text-[0.65rem] font-semibold uppercase tracking-[0.1em] text-board-muted">Remaining Capacity</th>
                        </tr>
                    </thead>
                    <tbody class="[perspective:800px] divide-y divide-board-line bg-board-ink">
                        @foreach ($this->metrics as $metric)
                            <tr
                                wire:click="toggleDrilldown({{ $metric->user_id }})"
                                wire:key="metric-row-{{ $metric->user_id }}-{{ $metric->performance_tier?->value }}-{{ $metric->h_prod }}"
                                class="animate-flap-cascade cursor-pointer transition hover:bg-board-flap {{ $expandedUserId === $metric->user_id ? 'bg-board-flap' : '' }}"
                            >
                                <td class="px-4 py-3 font-board text-sm font-semibold text-board-text">{{ $metric->user?->full_name ?? 'Unknown' }}</td>
                                <td class="hidden px-4 py-3 text-sm text-board-muted md:table-cell">{{ $metric->user?->designation?->name ?? '—' }}</td>
                                <td class="hidden px-4 py-3 font-slip text-sm tabular-nums text-board-text sm:table-cell">{{ number_format((float) $metric->h_prod, 2) }}h / {{ number_format((float) $metric->h_thresh, 2) }}h</td>
                                <td class="px-4 py-3"><x-tier-badge :tier="$metric->performance_tier" :percentage="$metric->performance_percentage" /></td>
                                <td class="px-4 py-3">
                                    <x-workload-bar
                                        :score="$metric->h_prod"
                                        :threshold="$metric->h_thresh"
                                        :tier="$metric->performance_tier"
                                        label="Hours used"
                                        class="w-32 sm:w-48"
                                    />
                                </td>
                            </tr>
                            @if ($expandedUserId === $metric->user_id)
                                <tr wire:key="metric-drilldown-{{ $metric->user_id }}">
                                    <td colspan="5" class="bg-slip-paper px-4 py-5 sm:px-6">
                                        <div class="max-w-[85vw] sm:max-w-none">
                                        <div class="border-b border-dashed border-slip-rule pb-3">
                                            <p class="font-board text-[0.65rem] font-semibold uppercase tracking-[0.12em] text-slip-ink/60">Formula drill-down</p>
                                        </div>
                                        <div class="mt-3 grid grid-cols-2 gap-x-6 gap-y-3 font-slip text-sm sm:grid-cols-3 lg:grid-cols-6">
                                            <div>
                                                <p class="text-[0.65rem] uppercase tracking-wider text-slip-ink/50">H_base</p>
                                                <p class="tabular-nums font-medium text-slip-ink">{{ number_format((float) $metric->h_base, 2) }}h</p>
                                            </div>
                                            <div>
                                                <p class="text-[0.65rem] uppercase tracking-wider text-slip-ink/50">H_leave</p>
                                                <p class="tabular-nums font-medium text-slip-ink">{{ number_format((float) $metric->h_leave, 2) }}h</p>
                                            </div>
                                            <div>
                                                <p class="text-[0.65rem] uppercase tracking-wider text-slip-ink/50">H_poss = H_base − H_leave</p>
                                                <p class="tabular-nums font-medium text-slip-ink">{{ number_format((float) $metric->h_poss, 2) }}h</p>
                                            </div>
                                            <div>
                                                <p class="text-[0.65rem] uppercase tracking-wider text-slip-ink/50">U_target</p>
                                                <p class="tabular-nums font-medium text-slip-ink">{{ number_format((float) $metric->u_target, 3) }}</p>
                                            </div>
                                            <div>
                                                <p class="text-[0.65rem] uppercase tracking-wider text-slip-ink/50">H_thresh = H_poss × U_target</p>
                                                <p class="tabular-nums font-medium text-slip-ink">{{ number_format((float) $metric->h_thresh, 2) }}h</p>
                                            </div>
                                            <div>
                                                <p class="text-[0.65rem] uppercase tracking-wider text-slip-ink/50">H_prod</p>
                                                <p class="tabular-nums font-medium text-slip-ink">{{ number_format((float) $metric->h_prod, 2) }}h</p>
                                            </div>
                                        </div>
                                        <p class="mt-4 break-words font-slip text-sm tabular-nums text-slip-ink">
                                            Performance % = (H_prod ÷ H_thresh) × 100 =
                                            @if (is_null($metric->performance_percentage))
                                                n/a (H_thresh = 0, full-month leave)
                                            @else
                                                ({{ number_format((float) $metric->h_prod, 2) }} ÷ {{ number_format((float) $metric->h_thresh, 2) }}) × 100 = {{ number_format((float) $metric->performance_percentage, 2) }}%
                                            @endif
                                        </p>
                                        <p class="mt-1 break-words font-slip text-sm tabular-nums text-slip-ink">
                                            Effective Availability = H_thresh − H_prod = {{ number_format((float) $metric->effective_availability_hours, 2) }}h
                                        </p>
                                        <p class="mt-3 text-xs text-slip-ink/50">Computed {{ $metric->computed_at?->diffForHumans() }}</p>
                                        </div>
                                    </td>
                                </tr>
                            @endif
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
