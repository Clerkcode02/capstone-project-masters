<div>
    <x-flash-messages />

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        @can('viewAny', \App\Models\CapacityMetric::class)
            <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                <h3 class="text-sm font-semibold text-gray-900">Monthly Capacity Report (PDF)</h3>
                <p class="mt-1 text-xs text-gray-500">Same figures as the Monthly At-a-Glance dashboard.</p>
                <div class="mt-4 flex flex-wrap items-end gap-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-500">Month</label>
                        <select wire:model="capacityMonth" class="mt-1 rounded-md border-gray-300 text-sm shadow-sm">
                            @foreach (range(1, 12) as $m)
                                <option value="{{ $m }}">{{ \Carbon\Carbon::create()->month($m)->format('F') }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500">Year</label>
                        <select wire:model="capacityYear" class="mt-1 rounded-md border-gray-300 text-sm shadow-sm">
                            @foreach (range(now()->year - 1, now()->year + 1) as $y)
                                <option value="{{ $y }}">{{ $y }}</option>
                            @endforeach
                        </select>
                    </div>
                    <button type="button" wire:click="queueMonthlyCapacityPdf" wire:loading.attr="disabled" wire:target="queueMonthlyCapacityPdf"
                        class="inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-indigo-500 disabled:cursor-not-allowed disabled:opacity-60">
                        <span wire:loading.remove wire:target="queueMonthlyCapacityPdf">Queue PDF</span>
                        <span wire:loading wire:target="queueMonthlyCapacityPdf">Queuing&hellip;</span>
                    </button>
                </div>
            </div>

            <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                <h3 class="text-sm font-semibold text-gray-900">Quarterly Capacity Report (PDF)</h3>
                <p class="mt-1 text-xs text-gray-500">Arithmetic mean of the three monthly percentages, per employee.</p>
                <div class="mt-4 flex flex-wrap items-end gap-3">
                    <div>
                        <label class="block text-xs font-medium text-gray-500">Quarter</label>
                        <select wire:model="quarter" class="mt-1 rounded-md border-gray-300 text-sm shadow-sm">
                            @foreach ([1, 2, 3, 4] as $q)
                                <option value="{{ $q }}">Q{{ $q }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-500">Year</label>
                        <select wire:model="quarterlyYear" class="mt-1 rounded-md border-gray-300 text-sm shadow-sm">
                            @foreach (range(now()->year - 1, now()->year + 1) as $y)
                                <option value="{{ $y }}">{{ $y }}</option>
                            @endforeach
                        </select>
                    </div>
                    <button type="button" wire:click="queueQuarterlyCapacityPdf" wire:loading.attr="disabled" wire:target="queueQuarterlyCapacityPdf"
                        class="inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-indigo-500 disabled:cursor-not-allowed disabled:opacity-60">
                        <span wire:loading.remove wire:target="queueQuarterlyCapacityPdf">Queue PDF</span>
                        <span wire:loading wire:target="queueQuarterlyCapacityPdf">Queuing&hellip;</span>
                    </button>
                </div>
            </div>

            <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                <h3 class="text-sm font-semibold text-gray-900">Team Workload Distribution</h3>
                <p class="mt-1 text-xs text-gray-500">Current workload score per employee against the threshold.</p>
                <div class="mt-4 flex flex-wrap gap-3">
                    <button type="button" wire:click="queueWorkloadPdf" wire:loading.attr="disabled" wire:target="queueWorkloadPdf"
                        class="inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-indigo-500 disabled:cursor-not-allowed disabled:opacity-60">
                        <span wire:loading.remove wire:target="queueWorkloadPdf">Queue PDF</span>
                        <span wire:loading wire:target="queueWorkloadPdf">Queuing&hellip;</span>
                    </button>
                    <button type="button" wire:click="downloadWorkloadExcel" wire:loading.attr="disabled" wire:target="downloadWorkloadExcel"
                        class="inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-60">
                        <span wire:loading.remove wire:target="downloadWorkloadExcel">Download Excel</span>
                        <span wire:loading wire:target="downloadWorkloadExcel">Preparing&hellip;</span>
                    </button>
                </div>
            </div>
        @endcan

        @can('viewAny', \App\Models\RedistributionRecommendation::class)
            <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                <h3 class="text-sm font-semibold text-gray-900">Bottleneck Report</h3>
                <p class="mt-1 text-xs text-gray-500">Bottleneck-triggered redistribution recommendations, all statuses.</p>
                <div class="mt-4 flex flex-wrap gap-3">
                    <button type="button" wire:click="queueBottleneckPdf" wire:loading.attr="disabled" wire:target="queueBottleneckPdf"
                        class="inline-flex items-center rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-indigo-500 disabled:cursor-not-allowed disabled:opacity-60">
                        <span wire:loading.remove wire:target="queueBottleneckPdf">Queue PDF</span>
                        <span wire:loading wire:target="queueBottleneckPdf">Queuing&hellip;</span>
                    </button>
                    <button type="button" wire:click="downloadBottleneckExcel" wire:loading.attr="disabled" wire:target="downloadBottleneckExcel"
                        class="inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-60">
                        <span wire:loading.remove wire:target="downloadBottleneckExcel">Download Excel</span>
                        <span wire:loading wire:target="downloadBottleneckExcel">Preparing&hellip;</span>
                    </button>
                </div>
            </div>
        @endcan

        <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
            <h3 class="text-sm font-semibold text-gray-900">Time Log Report</h3>
            <p class="mt-1 text-xs text-gray-500">
                @if ($this->isManagerOrAdmin())
                    All employees' logged time. Leave dates blank for all history.
                @else
                    Your own logged time only. Leave dates blank for all history.
                @endif
            </p>
            <div class="mt-4 flex flex-wrap items-end gap-3">
                <div>
                    <label class="block text-xs font-medium text-gray-500">From</label>
                    <input type="date" wire:model="timeLogFrom" class="mt-1 rounded-md border-gray-300 text-sm shadow-sm">
                </div>
                <div>
                    <label class="block text-xs font-medium text-gray-500">To</label>
                    <input type="date" wire:model="timeLogTo" class="mt-1 rounded-md border-gray-300 text-sm shadow-sm">
                </div>
                <button type="button" wire:click="downloadTimeLogsExcel" wire:loading.attr="disabled" wire:target="downloadTimeLogsExcel"
                    class="inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-60">
                    <span wire:loading.remove wire:target="downloadTimeLogsExcel">Download Excel</span>
                    <span wire:loading wire:target="downloadTimeLogsExcel">Preparing&hellip;</span>
                </button>
                <button type="button" wire:click="downloadTimeLogsCsv" wire:loading.attr="disabled" wire:target="downloadTimeLogsCsv"
                    class="inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-60">
                    <span wire:loading.remove wire:target="downloadTimeLogsCsv">Download CSV</span>
                    <span wire:loading wire:target="downloadTimeLogsCsv">Preparing&hellip;</span>
                </button>
            </div>
        </div>

        @can('viewAny', \App\Models\AuditLog::class)
            <div class="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                <h3 class="text-sm font-semibold text-gray-900">Audit Log Export</h3>
                <p class="mt-1 text-xs text-gray-500">Full compliance trail — administrator only.</p>
                <div class="mt-4">
                    <button type="button" wire:click="downloadAuditLogsCsv" wire:loading.attr="disabled" wire:target="downloadAuditLogsCsv"
                        class="inline-flex items-center rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 shadow-sm hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-60">
                        <span wire:loading.remove wire:target="downloadAuditLogsCsv">Download CSV</span>
                        <span wire:loading wire:target="downloadAuditLogsCsv">Preparing&hellip;</span>
                    </button>
                </div>
            </div>
        @endcan
    </div>

    <div class="mt-8">
        <h3 class="text-sm font-semibold text-gray-900">Queued Reports</h3>
        <p class="mt-1 text-xs text-gray-500">PDF generation runs in the background. Refresh this page once it's done.</p>

        <div wire:poll.10s class="mt-3 overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
            @if ($this->readyReports->isEmpty())
                <x-empty-state message="No reports queued yet." />
            @else
                <ul class="divide-y divide-gray-100">
                    @foreach ($this->readyReports as $notification)
                        <li class="flex items-center justify-between px-4 py-3">
                            <div>
                                <p class="text-sm font-medium text-gray-900">{{ $notification->data['label'] }}</p>
                                <p class="text-xs text-gray-400">Ready {{ $notification->created_at->diffForHumans() }}</p>
                            </div>
                            <button type="button" wire:click="downloadReady('{{ $notification->id }}')"
                                wire:loading.attr="disabled" wire:target="downloadReady('{{ $notification->id }}')"
                                class="inline-flex items-center rounded-md border border-gray-300 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 shadow-sm hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-60">
                                <span wire:loading.remove wire:target="downloadReady('{{ $notification->id }}')">Download</span>
                                <span wire:loading wire:target="downloadReady('{{ $notification->id }}')">Preparing&hellip;</span>
                            </button>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </div>
</div>
