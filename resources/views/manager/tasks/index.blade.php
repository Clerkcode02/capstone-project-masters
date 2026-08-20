<x-layouts.role>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Tasks') }}
            </h2>
            <a href="{{ route('manager.tasks.create') }}" wire:navigate
               class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">
                {{ __('New Task') }}
            </a>
        </div>
    </x-slot>

    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
        @if (session('status'))
            <div class="p-4 bg-green-50 text-green-700 text-sm">{{ session('status') }}</div>
        @endif

        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ __('Reference') }}</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ __('Title') }}</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ __('Account') }}</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ __('Tier') }}</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ __('Assignee') }}</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ __('Status') }}</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ __('Due') }}</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse ($tasks as $task)
                    <tr>
                        <td class="px-4 py-3 text-sm text-gray-900">{{ $task->reference }}</td>
                        <td class="px-4 py-3 text-sm text-gray-900">{{ $task->title }}</td>
                        <td class="px-4 py-3 text-sm text-gray-500">{{ $task->account->name }}</td>
                        <td class="px-4 py-3 text-sm text-gray-500">{{ $task->complexity_tier->label() }}</td>
                        <td class="px-4 py-3 text-sm text-gray-500">{{ $task->assignee?->full_name ?? __('Unassigned') }}</td>
                        <td class="px-4 py-3 text-sm text-gray-500">{{ $task->status->label() }}</td>
                        <td class="px-4 py-3 text-sm text-gray-500">{{ $task->due_date?->format('M j, Y') ?? '—' }}</td>
                        <td class="px-4 py-3 text-right text-sm">
                            <a href="{{ route('manager.tasks.edit', $task) }}" wire:navigate class="text-indigo-600 hover:text-indigo-900">{{ __('Edit') }}</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="px-4 py-6 text-center text-sm text-gray-500">{{ __('No tasks yet.') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>

        <div class="p-4">
            {{ $tasks->links() }}
        </div>
    </div>
</x-layouts.role>
