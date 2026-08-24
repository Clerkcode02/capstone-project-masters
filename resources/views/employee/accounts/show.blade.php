<x-layouts.role>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ $account->name }} ({{ $account->code }})
        </h2>
    </x-slot>

    <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
            <h3 class="font-medium text-gray-900 mb-4">{{ __('Teammates on this Account') }}</h3>

            <ul class="divide-y divide-gray-200">
                @forelse ($account->users as $employee)
                    <li class="py-3 text-sm text-gray-900">{{ $employee->full_name }}</li>
                @empty
                    <li class="py-3 text-sm text-gray-500">{{ __('No employees assigned yet.') }}</li>
                @endforelse
            </ul>
        </div>
    </div>
</x-layouts.role>
