<div class="bg-white shadow-sm rounded-lg p-6">
    <h3 class="text-lg font-semibold text-gray-800 mb-4">Manual entry</h3>

    <form wire:submit="save" class="space-y-4">
        <div>
            <label class="block text-sm font-medium text-gray-700">Account</label>
            <select wire:model.live="form.account_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                <option value="">Select an account</option>
                @foreach ($this->accounts as $account)
                    <option value="{{ $account->id }}">{{ $account->name }}</option>
                @endforeach
            </select>
            @error('form.account_id') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700">Task (optional)</label>
            <select wire:model="form.task_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                <option value="">No specific task</option>
                @foreach ($this->tasks as $task)
                    <option value="{{ $task->id }}">{{ $task->reference }} — {{ $task->title }}</option>
                @endforeach
            </select>
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700">Date</label>
                <input type="date" wire:model="form.log_date" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                @error('form.log_date') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Hours</label>
                <input type="number" step="0.25" wire:model="form.hours" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                @error('form.hours') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700">Hour type</label>
            <select wire:model="form.hour_type" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                <option value="production">Production</option>
                <option value="non_production">Non-Production</option>
                <option value="leave">Leave</option>
            </select>
            @error('form.hour_type') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700">Notes (optional)</label>
            <textarea wire:model="form.notes" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" rows="2"></textarea>
        </div>

        <button
            type="submit"
            wire:loading.attr="disabled"
            wire:target="save"
            class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-700 disabled:cursor-not-allowed disabled:opacity-60"
        >
            <svg wire:loading wire:target="save" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
            </svg>
            <span wire:loading.remove wire:target="save">Log time</span>
            <span wire:loading wire:target="save">Saving&hellip;</span>
        </button>
    </form>
</div>
