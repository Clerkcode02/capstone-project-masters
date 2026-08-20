<x-layouts.role>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Time Tracking') }}
        </h2>
    </x-slot>

    <div class="space-y-6">
        <livewire:time-tracking.manual-entry-form />
        <livewire:time-tracking.my-time-logs />
    </div>
</x-layouts.role>
