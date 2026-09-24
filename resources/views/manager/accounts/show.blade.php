<x-layouts.role>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ $account->name }} ({{ $account->code }})
        </h2>
    </x-slot>

    <div class="max-w-2xl mx-auto sm:px-6 lg:px-8 space-y-6">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
            <h3 class="font-medium text-gray-900 mb-4">{{ __('Assigned Employees') }}</h3>

            <ul class="divide-y divide-gray-200">
                @forelse ($account->users as $employee)
                    <li class="py-3 text-sm text-gray-900">{{ $employee->full_name }}</li>
                @empty
                    <li class="py-3">
                        <x-empty-state message="{{ __('No employees assigned yet.') }}" />
                    </li>
                @endforelse
            </ul>
        </div>

        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
            <h3 class="font-medium text-gray-900 mb-4">{{ __('Tasks') }}</h3>

            <ul class="divide-y divide-gray-200">
                @forelse ($account->tasks as $task)
                    <li class="py-3 flex items-center justify-between gap-3 text-sm">
                        <div>
                            <p class="text-gray-900 font-medium">{{ $task->reference }} — {{ $task->title }}</p>
                            <p class="text-gray-500">{{ $task->assignee?->full_name ?? 'Unassigned' }}</p>
                        </div>
                        <x-complexity-badge :tier="$task->complexity_tier" />
                    </li>
                @empty
                    <li class="py-3">
                        <x-empty-state message="{{ __('No tasks yet.') }}" />
                    </li>
                @endforelse
            </ul>
        </div>

        @can('create', \App\Models\Task::class)
            <livewire:tasks.assign-task-modal :account="$account" :key="'assign-task-'.$account->id" />
        @endcan
    </div>
</x-layouts.role>
