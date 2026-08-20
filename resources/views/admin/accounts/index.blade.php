<x-layouts.role>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('Accounts') }}
            </h2>
            <a href="{{ route('admin.accounts.create') }}" wire:navigate
               class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700">
                {{ __('New Account') }}
            </a>
        </div>
    </x-slot>

    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-4">
        @if (session('status'))
            <div class="p-4 bg-green-50 text-green-700 rounded-md text-sm">{{ session('status') }}</div>
        @endif

        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ __('Name') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ __('Code') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ __('Assigned') }}</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">{{ __('Status') }}</th>
                        <th class="px-6 py-3"></th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse ($accounts as $account)
                        <tr>
                            <td class="px-6 py-4 text-sm text-gray-900">{{ $account->name }}</td>
                            <td class="px-6 py-4 text-sm text-gray-500">{{ $account->code }}</td>
                            <td class="px-6 py-4 text-sm text-gray-500">{{ $account->users_count }}</td>
                            <td class="px-6 py-4 text-sm">
                                @if ($account->is_active)
                                    <span class="px-2 py-1 text-xs font-medium rounded-full bg-green-100 text-green-800">{{ __('Active') }}</span>
                                @else
                                    <span class="px-2 py-1 text-xs font-medium rounded-full bg-gray-100 text-gray-600">{{ __('Inactive') }}</span>
                                @endif
                            </td>
                            <td class="px-6 py-4 text-right text-sm space-x-3">
                                <a href="{{ route('admin.accounts.edit', $account) }}" wire:navigate class="text-indigo-600 hover:text-indigo-900">{{ __('Manage') }}</a>
                                <form method="POST" action="{{ route('admin.accounts.destroy', $account) }}" class="inline"
                                      onsubmit="return confirm('{{ __('Delete this account?') }}');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:text-red-900">{{ __('Delete') }}</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-6 py-4 text-sm text-gray-500 text-center">{{ __('No accounts yet.') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{ $accounts->links() }}
    </div>
</x-layouts.role>
