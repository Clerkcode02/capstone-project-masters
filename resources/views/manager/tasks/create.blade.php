<x-layouts.role>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('New Task') }}
        </h2>
    </x-slot>

    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
        <form method="POST" action="{{ route('manager.tasks.store') }}">
            @csrf

            @include('manager.tasks._form')

            <div class="mt-6 flex items-center gap-4">
                <x-primary-button>{{ __('Create Task') }}</x-primary-button>
                <a href="{{ route('manager.tasks.index') }}" wire:navigate class="text-sm text-gray-600">{{ __('Cancel') }}</a>
            </div>
        </form>
    </div>
</x-layouts.role>
