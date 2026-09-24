<div class="bg-white shadow-sm rounded-lg p-6" wire:poll.30s>
    <h3 class="text-lg font-semibold text-gray-800 mb-4">Timer</h3>

    @error('timer')
        <div class="mb-4 text-sm text-red-600">{{ $message }}</div>
    @enderror

    @if ($this->active)
        <div class="space-y-4">
            <div class="text-3xl font-mono text-gray-800">{{ $this->elapsedLabel }}</div>
            <p class="text-sm text-gray-500">Running since {{ \Illuminate\Support\Carbon::parse($this->active['started_at'])->format('g:i A') }}</p>

            <div>
                <label class="block text-sm font-medium text-gray-700">Notes (optional)</label>
                <textarea wire:model="notes" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" rows="2"></textarea>
            </div>

            <button
                wire:click="stop"
                wire:loading.attr="disabled"
                wire:target="stop"
                class="inline-flex items-center gap-2 px-4 py-2 bg-red-600 text-white text-sm font-medium rounded-md hover:bg-red-700 disabled:cursor-not-allowed disabled:opacity-60"
            >
                <svg wire:loading wire:target="stop" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                </svg>
                <span wire:loading.remove wire:target="stop">Stop timer</span>
                <span wire:loading wire:target="stop">Stopping&hellip;</span>
            </button>
        </div>
    @else
        <div class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700">Account</label>
                <select wire:model.live="account_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                    <option value="">Select an account</option>
                    @foreach ($this->accounts as $account)
                        <option value="{{ $account->id }}">{{ $account->name }}</option>
                    @endforeach
                </select>
                @error('account_id') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Task (optional)</label>
                <select wire:model="task_id" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                    <option value="">No specific task</option>
                    @foreach ($this->tasks as $task)
                        <option value="{{ $task->id }}">{{ $task->reference }} — {{ $task->title }}</option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Hour type</label>
                <select wire:model="hour_type" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                    <option value="production">Production</option>
                    <option value="non_production">Non-Production</option>
                    <option value="leave">Leave</option>
                </select>
            </div>

            <button
                wire:click="start"
                wire:loading.attr="disabled"
                wire:target="start"
                class="inline-flex items-center gap-2 px-4 py-2 bg-green-600 text-white text-sm font-medium rounded-md hover:bg-green-700 disabled:cursor-not-allowed disabled:opacity-60"
            >
                <svg wire:loading wire:target="start" class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                </svg>
                <span wire:loading.remove wire:target="start">Start timer</span>
                <span wire:loading wire:target="start">Starting&hellip;</span>
            </button>
        </div>
    @endif
</div>
