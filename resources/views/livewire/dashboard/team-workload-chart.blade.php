<div>
    @if ($this->scores->isEmpty())
        <x-empty-state message="No active employees with in-progress tasks yet." />
    @else
        <div
            wire:ignore
            x-data="workloadChart(
                @js($this->scores),
                {{ $this->threshold }}
            )"
            class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm"
        >
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-sm font-semibold text-gray-900">Team Workload</h3>
                    <p class="text-xs text-gray-400">
                        Workload score per employee, sorted highest first. Dashed line marks the
                        threshold ({{ $this->threshold }} points); bars past it signal over-allocation.
                    </p>
                </div>
                <button
                    type="button"
                    @click="showTable = ! showTable"
                    class="shrink-0 rounded-md border border-gray-300 px-3 py-1.5 text-xs font-medium text-gray-600 hover:bg-gray-50"
                    :aria-pressed="showTable"
                >
                    <span x-show="! showTable">View as table</span>
                    <span x-show="showTable" x-cloak>View as chart</span>
                </button>
            </div>

            <div class="mt-4" x-show="! showTable" style="height: {{ max(160, count($this->scores) * 36) }}px">
                <canvas x-ref="canvas" role="img" aria-label="Horizontal bar chart of workload score per employee"></canvas>
            </div>

            <div class="mt-4 overflow-x-auto" x-show="showTable" x-cloak>
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <caption class="sr-only">Workload score per employee, sorted highest first</caption>
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-4 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Employee</th>
                            <th scope="col" class="px-4 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Designation</th>
                            <th scope="col" class="px-4 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Workload Score</th>
                            <th scope="col" class="px-4 py-2 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach ($this->scores as $row)
                            <tr>
                                <td class="px-4 py-2 font-medium text-gray-900">{{ $row['name'] }}</td>
                                <td class="px-4 py-2 text-gray-500">{{ $row['designation'] ?? '—' }}</td>
                                <td class="px-4 py-2 font-mono text-gray-700">{{ $row['score'] }} / {{ $this->threshold }}</td>
                                <td class="px-4 py-2">
                                    @if ($row['score'] > $this->threshold)
                                        <span class="inline-flex items-center gap-1.5 rounded-full border bg-rose-50 px-2.5 py-1 text-xs font-medium text-rose-700 border-rose-200">
                                            <span class="h-1.5 w-1.5 rounded-full bg-current"></span>
                                            Over threshold
                                        </span>
                                    @else
                                        <span class="inline-flex items-center gap-1.5 rounded-full border bg-emerald-50 px-2.5 py-1 text-xs font-medium text-emerald-700 border-emerald-200">
                                            <span class="h-1.5 w-1.5 rounded-full bg-current"></span>
                                            Within threshold
                                        </span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>
