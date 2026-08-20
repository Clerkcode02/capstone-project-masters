<x-layouts.role>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Accounts') }}
        </h2>
    </x-slot>

    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ __('Name') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ __('Code') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ __('Assigned') }}</th>
                        <th class="px-6 py-3"></th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse ($accounts as $account)
                        <tr>
                            <td class="px-6 py-4 text-sm text-gray-900">{{ $account->name }}</td>
                            <td class="px-6 py-4 text-sm text-gray-500">{{ $account->code }}</td>
                            <td class="px-6 py-4 text-sm text-gray-500">{{ $account->users_count }}</td>
                            <td class="px-6 py-4 text-right text-sm">
                                <a href="{{ route('manager.accounts.show', $account) }}" wire:navigate class="text-indigo-600 hover:text-indigo-900">{{ __('View') }}</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-6 py-4 text-sm text-gray-500 text-center">{{ __('No accounts yet.') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $accounts->links() }}
    </div>
</x-layouts.role>
