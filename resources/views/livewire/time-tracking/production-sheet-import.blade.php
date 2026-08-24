<div class="bg-white shadow-sm rounded-lg p-6">
    <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-semibold text-gray-800">Production sheet import</h3>
        <a href="{{ route('time-tracking.import.template') }}" class="text-sm text-indigo-600 hover:underline">
            Download CSV template
        </a>
    </div>

    @if ($queuedMessage)
        <div class="mb-4 p-3 bg-green-50 text-green-800 text-sm rounded-md">{{ $queuedMessage }}</div>
    @endif

    @if (! $previewed)
        <form wire:submit="uploadAndPreview" class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700">Production sheet (CSV or XLSX)</label>
                <input type="file" wire:model="sheet" class="mt-1 block w-full">
                @error('sheet') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
                <div wire:loading wire:target="sheet" class="text-sm text-gray-500 mt-1">Uploading…</div>
            </div>

            <button type="submit" class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-700">
                Upload &amp; preview
            </button>
        </form>
    @else
        <div class="space-y-6">
            <p class="text-sm text-gray-600">
                <strong>{{ $originalFilename }}</strong> —
                {{ count($validRows) }} valid row(s), {{ count($invalidRows) }} rejected row(s).
            </p>

            @if (count($validRows) > 0)
                <div>
                    <h4 class="font-medium text-green-700 mb-2">Valid rows ({{ count($validRows) }})</h4>
                    <div class="overflow-x-auto border rounded-md">
                        <table class="min-w-full text-sm">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-3 py-2 text-left">Row</th>
                                    <th class="px-3 py-2 text-left">Log date</th>
                                    <th class="px-3 py-2 text-left">Hour type</th>
                                    <th class="px-3 py-2 text-left">Minutes</th>
                                    <th class="px-3 py-2 text-left">Notes</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($validRows as $row)
                                    <tr class="border-t">
                                        <td class="px-3 py-2">{{ $row['row'] }}</td>
                                        <td class="px-3 py-2">{{ $row['data']['log_date'] }}</td>
                                        <td class="px-3 py-2">{{ $row['data']['hour_type'] }}</td>
                                        <td class="px-3 py-2">{{ $row['data']['duration_minutes'] }}</td>
                                        <td class="px-3 py-2">{{ $row['data']['notes'] }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif

            @if (count($invalidRows) > 0)
                <div>
                    <h4 class="font-medium text-red-700 mb-2">Rejected rows ({{ count($invalidRows) }})</h4>
                    <div class="overflow-x-auto border rounded-md">
                        <table class="min-w-full text-sm">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-3 py-2 text-left">Row</th>
                                    <th class="px-3 py-2 text-left">Raw data</th>
                                    <th class="px-3 py-2 text-left">Reasons</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($invalidRows as $row)
                                    <tr class="border-t align-top">
                                        <td class="px-3 py-2">{{ $row['row'] }}</td>
                                        <td class="px-3 py-2 text-gray-600">{{ json_encode($row['data']) }}</td>
                                        <td class="px-3 py-2 text-red-600">
                                            <ul class="list-disc list-inside">
                                                @foreach ($row['reasons'] as $reason)
                                                    <li>{{ $reason }}</li>
                                                @endforeach
                                            </ul>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif

            <div class="flex gap-3">
                <button
                    wire:click="confirmImport"
                    @if (count($validRows) === 0) disabled @endif
                    class="inline-flex items-center px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-700 disabled:opacity-50 disabled:cursor-not-allowed"
                >
                    Confirm import ({{ count($validRows) }} row(s))
                </button>

                <button wire:click="cancel" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 text-gray-700 text-sm font-medium rounded-md hover:bg-gray-50">
                    Cancel
                </button>
            </div>
        </div>
    @endif
</div>
