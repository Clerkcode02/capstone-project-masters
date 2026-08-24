<x-layouts.role>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Manage Account') }} — {{ $account->name }}
        </h2>
    </x-slot>

    <div class="max-w-2xl mx-auto sm:px-6 lg:px-8 space-y-6">
        @if (session('status'))
            <div class="p-4 bg-green-50 text-green-700 rounded-md text-sm">{{ session('status') }}</div>
        @endif

        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
            <form method="POST" action="{{ route('admin.accounts.update', $account) }}" class="space-y-6">
                @csrf
                @method('PUT')

                <div>
                    <x-input-label for="name" :value="__('Account Name')" />
                    <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $account->name)" required />
                    <x-input-error :messages="$errors->get('name')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="code" :value="__('Account Code')" />
                    <x-text-input id="code" name="code" type="text" class="mt-1 block w-full" :value="old('code', $account->code)" required />
                    <x-input-error :messages="$errors->get('code')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="expected_monthly_hours" :value="__('Expected Monthly Hours')" />
                    <x-text-input id="expected_monthly_hours" name="expected_monthly_hours" type="number" step="0.01" min="0"
                                  class="mt-1 block w-full" :value="old('expected_monthly_hours', $account->expected_monthly_hours)" />
                    <x-input-error :messages="$errors->get('expected_monthly_hours')" class="mt-2" />
                </div>

                <div class="flex items-center gap-2">
                    <input type="checkbox" id="is_active" name="is_active" value="1" @checked(old('is_active', $account->is_active))
                           class="rounded border-gray-300 text-indigo-600 shadow-sm">
                    <x-input-label for="is_active" :value="__('Active')" />
                </div>

                <div class="flex items-center justify-end gap-4">
                    <x-primary-button>{{ __('Save Changes') }}</x-primary-button>
                </div>
            </form>
        </div>

        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
            <h3 class="font-medium text-gray-900 mb-4">{{ __('Assigned Employees') }}</h3>

            <ul class="divide-y divide-gray-200 mb-6">
                @forelse ($account->users as $employee)
                    <li class="py-3 flex items-center justify-between">
                        <span class="text-sm text-gray-900">{{ $employee->full_name }}</span>
                        <form method="POST" action="{{ route('admin.accounts.users.unassign', [$account, $employee]) }}">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="text-sm text-red-600 hover:text-red-900">{{ __('Unassign') }}</button>
                        </form>
                    </li>
                @empty
                    <li class="py-3 text-sm text-gray-500">{{ __('No employees assigned yet.') }}</li>
                @endforelse
            </ul>

            @if ($assignableUsers->isNotEmpty())
                <form method="POST" action="{{ route('admin.accounts.users.assign', $account) }}" class="flex items-end gap-4">
                    @csrf
                    <div class="flex-1">
                        <x-input-label for="user_id" :value="__('Assign Employee')" />
                        <select id="user_id" name="user_id" required
                                class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                            @foreach ($assignableUsers as $employee)
                                <option value="{{ $employee->id }}">{{ $employee->full_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <x-primary-button>{{ __('Assign') }}</x-primary-button>
                </form>
            @else
                <p class="text-sm text-gray-500">{{ __('All employees are already assigned to this account.') }}</p>
            @endif
        </div>
    </div>
</x-layouts.role>
