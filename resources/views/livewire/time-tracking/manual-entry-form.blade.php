<div class="bg-white border border-gray-200 rounded-lg shadow-sm p-6">
    <h3 class="text-sm font-semibold text-gray-900 mb-4">{{ __('Log time manually') }}</h3>

    @if (session('status'))
        <div class="mb-4 text-sm text-emerald-700 bg-emerald-50 border border-emerald-200 rounded-md px-3 py-2">
            {{ session('status') }}
        </div>
    @endif

    <form wire:submit="save" class="grid grid-cols-1 sm:grid-cols-2 gap-4">
        <div>
            <label class="block text-xs text-gray-500 mb-1">{{ __('Date') }}</label>
            <input type="date" wire:model="log_date" class="w-full rounded-md border-gray-300 text-sm">
            @error('log_date') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="block text-xs text-gray-500 mb-1">{{ __('Hour type') }}</label>
            <select wire:model="hour_type" class="w-full rounded-md border-gray-300 text-sm">
                @foreach ($this->hourTypes as $type)
                    <option value="{{ $type->value }}">{{ $type->label() }}</option>
                @endforeach
            </select>
            @error('hour_type') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="block text-xs text-gray-500 mb-1">{{ __('Task (optional)') }}</label>
            <select wire:model="task_id" class="w-full rounded-md border-gray-300 text-sm">
                <option value="">{{ __('No task') }}</option>
                @foreach ($this->tasks as $task)
                    <option value="{{ $task->id }}">{{ $task->title }}</option>
                @endforeach
            </select>
            @error('task_id') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="block text-xs text-gray-500 mb-1">{{ __('Account') }}</label>
            <select wire:model="account_id" class="w-full rounded-md border-gray-300 text-sm">
                <option value="">{{ __('No account') }}</option>
                @foreach ($this->accounts as $account)
                    <option value="{{ $account->id }}">{{ $account->name }}</option>
                @endforeach
            </select>
            @error('account_id') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="block text-xs text-gray-500 mb-1">{{ __('Duration (minutes)') }}</label>
            <input type="number" min="1" max="1440" wire:model="duration_minutes" class="w-full rounded-md border-gray-300 text-sm">
            @error('duration_minutes') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
        </div>

        <div class="sm:col-span-2">
            <label class="block text-xs text-gray-500 mb-1">{{ __('Notes') }}</label>
            <input type="text" wire:model="notes" maxlength="255" class="w-full rounded-md border-gray-300 text-sm">
            @error('notes') <p class="mt-1 text-xs text-rose-600">{{ $message }}</p> @enderror
        </div>

        <div class="sm:col-span-2">
            <button
                type="submit"
                wire:loading.attr="disabled"
                class="inline-flex items-center px-4 py-2 border border-transparent rounded-md text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 disabled:opacity-50"
            >
                {{ __('Save log') }}
            </button>
        </div>
    </form>
</div>
