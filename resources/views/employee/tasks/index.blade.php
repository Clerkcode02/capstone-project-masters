<x-layouts.role>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('My Tasks') }}
        </h2>
    </x-slot>

    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
        @if (session('status'))
            <div class="p-4 bg-green-50 text-green-700 text-sm">{{ session('status') }}</div>
        @endif

        @if ($errors->any())
            <div class="p-4 bg-red-50 text-red-700 text-sm">{{ $errors->first() }}</div>
        @endif

        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ __('Reference') }}</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ __('Title') }}</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ __('Account') }}</th>
                    <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ __('Tier') }}</th>
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
                        <td class="px-4 py-3 text-sm text-gray-500">{{ $task->status->label() }}</td>
                        <td class="px-4 py-3 text-sm text-gray-500">{{ $task->due_date?->format('M j, Y') ?? '—' }}</td>
                        <td class="px-4 py-3 text-right text-sm">
                            @if ($task->status === \App\Domain\Tasks\Enums\TaskStatus::Pending)
                                <form method="POST" action="{{ route('my.tasks.start', $task) }}">
                                    @csrf
                                    @method('PATCH')
                                    <x-secondary-button>{{ __('Start') }}</x-secondary-button>
                                </form>
                            @elseif ($task->status === \App\Domain\Tasks\Enums\TaskStatus::InProgress)
                                <form method="POST" action="{{ route('my.tasks.complete', $task) }}">
                                    @csrf
                                    @method('PATCH')
                                    <x-secondary-button>{{ __('Mark Complete') }}</x-secondary-button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-6 text-center text-sm text-gray-500">{{ __('No tasks assigned to you yet.') }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</x-layouts.role>
