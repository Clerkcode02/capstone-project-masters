<div class="bg-white shadow-sm sm:rounded-lg p-6 space-y-6">
    @if (session('status'))
        <div class="text-sm text-green-700">{{ session('status') }}</div>
    @endif

    @if (! $previewed)
        <form wire:submit="upload" class="space-y-4">
            <div>
                <x-input-label for="sheet" :value="__('Production sheet (CSV or XLSX)')" />
                <input type="file" id="sheet" wire:model="sheet" class="mt-1 block w-full text-sm" accept=".csv,.txt,.xlsx">
                <x-input-error :messages="$errors->get('sheet')" class="mt-2" />
                <p class="mt-2 text-sm text-gray-500">
                    <a href="{{ route('manager.production-sheet-template') }}" class="text-indigo-600 hover:underline">
                        {{ __('Download the sample template') }}
                    </a>
                </p>
            </div>

            <x-primary-button wire:loading.attr="disabled">{{ __('Upload and preview') }}</x-primary-button>
        </form>
    @else
        <div>
            <h3 class="text-lg font-medium text-gray-900">
                {{ __('Preview') }} &mdash;
                {{ count($validRows) }} {{ __('valid') }},
                {{ count($rejectedRows) }} {{ __('rejected') }}
            </h3>
        </div>

        @if ($validRows !== [])
            <div>
                <h4 class="font-semibold text-green-700 mb-2">{{ __('Valid rows') }}</h4>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm text-left">
                        <thead>
                            <tr class="text-gray-500">
                                <th class="pr-4">{{ __('Row') }}</th>
                                <th class="pr-4">{{ __('Employee') }}</th>
                                <th class="pr-4">{{ __('Date') }}</th>
                                <th class="pr-4">{{ __('Account') }}</th>
                                <th class="pr-4">{{ __('Hour type') }}</th>
                                <th class="pr-4">{{ __('Minutes') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($validRows as $row)
                                <tr class="border-t">
                                    <td class="pr-4 py-1">{{ $row['row'] }}</td>
                                    <td class="pr-4 py-1">{{ $row['employee_code'] }} &mdash; {{ $row['employee_name'] }}</td>
                                    <td class="pr-4 py-1">{{ $row['log_date'] }}</td>
                                    <td class="pr-4 py-1">{{ $row['account_code'] ?? '—' }}</td>
                                    <td class="pr-4 py-1">{{ $row['hour_type'] }}</td>
                                    <td class="pr-4 py-1">{{ $row['duration_minutes'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        @if ($rejectedRows !== [])
            <div>
                <h4 class="font-semibold text-red-700 mb-2">{{ __('Rejected rows') }}</h4>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm text-left">
                        <thead>
                            <tr class="text-gray-500">
                                <th class="pr-4">{{ __('Row') }}</th>
                                <th class="pr-4">{{ __('Reasons') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($rejectedRows as $row)
                                <tr class="border-t align-top">
                                    <td class="pr-4 py-1">{{ $row['row'] }}</td>
                                    <td class="pr-4 py-1">
                                        <ul class="list-disc list-inside text-red-600">
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
            @if ($validRows === [])
                <x-primary-button disabled>{{ __('Confirm import') }}</x-primary-button>
            @else
                <x-primary-button wire:click="confirm">{{ __('Confirm import') }}</x-primary-button>
            @endif
            <x-secondary-button wire:click="cancel">{{ __('Cancel') }}</x-secondary-button>
        </div>
    @endif
</div>
