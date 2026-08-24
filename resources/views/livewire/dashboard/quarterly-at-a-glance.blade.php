<div>
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div class="flex items-end gap-3">
            <div>
                <label for="quarter" class="block text-xs font-medium text-gray-500">Quarter</label>
                <select id="quarter" wire:model.live="quarter" class="mt-1 rounded-md border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    @foreach ([1, 2, 3, 4] as $q)
                        <option value="{{ $q }}">Q{{ $q }}</option>
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
    </div>

    <div class="mt-6 overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
        @if ($this->rows->isEmpty())
            <x-empty-state message="No monthly capacity metrics found for this quarter yet. Recalculate each month on the Monthly At-a-Glance screen first." />
        @else
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Employee</th>
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Designation</th>
                            @foreach ($this->months as $month)
                                <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">{{ \Carbon\Carbon::create()->month($month)->format('F') }}</th>
                            @endforeach
                            <th class="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Quarterly</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach ($this->rows as $row)
                            <tr>
                                <td class="px-4 py-3 text-sm font-medium text-gray-900">{{ $row['user']?->full_name ?? 'Unknown' }}</td>
                                <td class="px-4 py-3 text-sm text-gray-500">{{ $row['user']?->designation?->name ?? '—' }}</td>
                                @foreach ($this->months as $month)
                                    <td class="px-4 py-3">
                                        @php $metric = $row['months'][$month] ?? null; @endphp
                                        @if ($metric)
                                            <x-tier-badge :tier="$metric->performance_tier" :percentage="$metric->performance_percentage" />
                                        @else
                                            <span class="text-xs text-gray-400">No data</span>
                                        @endif
                                    </td>
                                @endforeach
                                <td class="px-4 py-3">
                                    <x-tier-badge :tier="$row['quarterly_tier']" :percentage="$row['quarterly_percentage']" />
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    <p class="mt-3 text-xs text-gray-400">
        Quarterly percentage is the arithmetic mean of the three monthly performance percentages shown above.
        A month on full-month leave (Not Applicable) is excluded from the mean; if all three months are excluded,
        the quarter is marked Not Applicable.
    </p>
</div>
