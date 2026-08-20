<div class="bg-white shadow-sm sm:rounded-lg p-6">
    <h3 class="text-lg font-medium text-gray-900 mb-4">{{ __('Log time manually') }}</h3>

    @if (session('status'))
        <div class="mb-4 text-sm text-green-700">{{ session('status') }}</div>
    @endif

    <form wire:submit="save" class="space-y-4">
        <div>
            <x-input-label for="manual-account" :value="__('Account')" />
            <select id="manual-account" wire:model="accountId" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                <option value="">{{ __('None') }}</option>
                @foreach ($accounts as $account)
                    <option value="{{ $account->id }}">{{ $account->name }}</option>
                @endforeach
            </select>
            <x-input-error :messages="$errors->get('accountId')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="manual-log-date" :value="__('Date')" />
            <x-text-input id="manual-log-date" type="date" wire:model="logDate" class="mt-1 block w-full" />
            <x-input-error :messages="$errors->get('logDate')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="manual-hour-type" :value="__('Hour type')" />
            <select id="manual-hour-type" wire:model="hourType" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                @foreach ($hourTypes as $type)
                    <option value="{{ $type->value }}">{{ $type->label() }}</option>
                @endforeach
            </select>
            <x-input-error :messages="$errors->get('hourType')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="manual-duration" :value="__('Duration (minutes)')" />
            <x-text-input id="manual-duration" type="number" min="1" max="1440" wire:model="durationMinutes" class="mt-1 block w-full" />
            <x-input-error :messages="$errors->get('durationMinutes')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="manual-notes" :value="__('Notes')" />
            <x-text-input id="manual-notes" type="text" wire:model="notes" class="mt-1 block w-full" />
            <x-input-error :messages="$errors->get('notes')" class="mt-2" />
        </div>

        <x-primary-button>{{ __('Save entry') }}</x-primary-button>
    </form>
</div>
