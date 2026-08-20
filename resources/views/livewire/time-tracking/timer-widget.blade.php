<div class="bg-white shadow-sm sm:rounded-lg p-6">
    <h3 class="text-lg font-medium text-gray-900 mb-4">{{ __('Timer') }}</h3>

    @if ($activeTimeLogId)
        <p class="text-sm text-gray-600 mb-4">{{ __('A timer is currently running.') }}</p>
        <button type="button" wire:click="stop" class="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-700">
            {{ __('Stop timer') }}
        </button>
    @else
        <div class="space-y-4">
            <div>
                <x-input-label for="timer-account" :value="__('Account')" />
                <select id="timer-account" wire:model="accountId" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                    <option value="">{{ __('Select an account') }}</option>
                    @foreach ($accounts as $account)
                        <option value="{{ $account->id }}">{{ $account->name }}</option>
                    @endforeach
                </select>
                <x-input-error :messages="$errors->get('accountId')" class="mt-2" />
            </div>

            <button type="button" wire:click="start" class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700">
                {{ __('Start timer') }}
            </button>
        </div>
    @endif
</div>
