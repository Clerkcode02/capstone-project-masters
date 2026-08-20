<x-layouts.role>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('New Account') }}
        </h2>
    </x-slot>

    <div class="max-w-2xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg p-6">
            <form method="POST" action="{{ route('admin.accounts.store') }}" class="space-y-6">
                @csrf

                <div>
                    <x-input-label for="name" :value="__('Account Name')" />
                    <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name')" required autofocus />
                    <x-input-error :messages="$errors->get('name')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="code" :value="__('Account Code')" />
                    <x-text-input id="code" name="code" type="text" class="mt-1 block w-full" :value="old('code')" required />
                    <x-input-error :messages="$errors->get('code')" class="mt-2" />
                </div>

                <div>
                    <x-input-label for="expected_monthly_hours" :value="__('Expected Monthly Hours')" />
                    <x-text-input id="expected_monthly_hours" name="expected_monthly_hours" type="number" step="0.01" min="0"
                                  class="mt-1 block w-full" :value="old('expected_monthly_hours')" />
                    <x-input-error :messages="$errors->get('expected_monthly_hours')" class="mt-2" />
                </div>

                <div class="flex items-center justify-end gap-4">
                    <a href="{{ route('admin.accounts.index') }}" wire:navigate class="text-sm text-gray-600 hover:text-gray-900">{{ __('Cancel') }}</a>
                    <x-primary-button>{{ __('Create Account') }}</x-primary-button>
                </div>
            </form>
        </div>
    </div>
</x-layouts.role>
