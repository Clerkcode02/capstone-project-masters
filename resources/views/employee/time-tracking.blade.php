<x-layouts.role>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Time Tracking') }}
        </h2>
    </x-slot>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <livewire:time-tracking.timer-widget />
        <livewire:time-tracking.manual-entry-form />
    </div>
</x-layouts.role>
