<div class="bg-white border border-gray-200 rounded-lg shadow-sm px-4 py-3">
    @if ($this->activeTimer)
        <div
            x-data="{
                startedAt: new Date(@js($this->activeTimer->started_at->toIso8601String())).getTime(),
                elapsed: '00:00:00',
                tick() {
                    const diff = Math.max(0, Math.floor((Date.now() - this.startedAt) / 1000));
                    const h = String(Math.floor(diff / 3600)).padStart(2, '0');
                    const m = String(Math.floor((diff % 3600) / 60)).padStart(2, '0');
                    const s = String(diff % 60).padStart(2, '0');
                    this.elapsed = `${h}:${m}:${s}`;
                }
            }"
            x-init="tick(); setInterval(() => tick(), 1000)"
            class="flex items-center gap-4"
        >
            <span class="inline-flex h-2 w-2 rounded-full bg-rose-500 animate-pulse"></span>
            <div>
                <p class="text-xs text-gray-500">
                    {{ __('Timer running') }}
                    @if ($this->activeTimer->task)
                        &middot; {{ $this->activeTimer->task->title }}
                    @endif
                </p>
                <p class="text-lg font-mono font-semibold text-gray-900" x-text="elapsed"></p>
            </div>
            <button
                type="button"
                wire:click="stop"
                wire:loading.attr="disabled"
                class="ml-auto inline-flex items-center px-3 py-2 border border-transparent rounded-md text-sm font-medium text-white bg-rose-600 hover:bg-rose-700 disabled:opacity-50"
            >
                {{ __('Stop') }}
            </button>
        </div>
    @else
        <form wire:submit="start" class="flex flex-wrap items-end gap-3">
            <div>
                <label class="block text-xs text-gray-500 mb-1">{{ __('Task (optional)') }}</label>
                <select wire:model="taskId" class="rounded-md border-gray-300 text-sm">
                    <option value="">{{ __('No task') }}</option>
                    @foreach ($this->tasks as $task)
                        <option value="{{ $task->id }}">{{ $task->title }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs text-gray-500 mb-1">{{ __('Account') }}</label>
                <select wire:model="accountId" class="rounded-md border-gray-300 text-sm">
                    <option value="">{{ __('No account') }}</option>
                    @foreach ($this->accounts as $account)
                        <option value="{{ $account->id }}">{{ $account->name }}</option>
                    @endforeach
                </select>
            </div>
            <button
                type="submit"
                wire:loading.attr="disabled"
                class="inline-flex items-center px-3 py-2 border border-transparent rounded-md text-sm font-medium text-white bg-emerald-600 hover:bg-emerald-700 disabled:opacity-50"
            >
                {{ __('Start timer') }}
            </button>
        </form>
    @endif
</div>
