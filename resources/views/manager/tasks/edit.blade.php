<x-layouts.role>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Edit Task') }} — {{ $task->reference }}
        </h2>
    </x-slot>

    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
        <form method="POST" action="{{ route('manager.tasks.update', $task) }}">
            @csrf
            @method('PUT')

            @include('manager.tasks._form')

            <div class="mt-6 flex items-center gap-4">
                <x-primary-button>{{ __('Save Changes') }}</x-primary-button>
                <a href="{{ route('manager.tasks.index') }}" wire:navigate class="text-sm text-gray-600">{{ __('Cancel') }}</a>
            </div>
        </form>

        <form method="POST" action="{{ route('manager.tasks.destroy', $task) }}" class="mt-6 pt-6 border-t border-gray-200"
              onsubmit="return confirm('{{ __('Delete this task?') }}')">
            @csrf
            @method('DELETE')
            <x-danger-button>{{ __('Delete Task') }}</x-danger-button>
        </form>
    </div>
</x-layouts.role>
