<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Time Tracking') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-8">
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <livewire:time-tracking.timer-widget />
                <livewire:time-tracking.manual-entry-form />
            </div>

            @can('import', \App\Models\TimeLog::class)
                <livewire:time-tracking.production-sheet-import />
            @endcan
        </div>
    </div>
</x-app-layout>
