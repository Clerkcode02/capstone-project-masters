<div class="bg-white border border-gray-200 rounded-lg shadow-sm p-6">
    <div class="flex items-center justify-between mb-4">
        <h3 class="text-sm font-semibold text-gray-900">{{ __('My time logs') }}</h3>
        <div class="flex items-center gap-3">
            <input type="month" wire:model.live="month" class="rounded-md border-gray-300 text-sm">
            <span class="text-xs text-gray-500">{{ __('Total') }}: {{ number_format($this->totalHours, 2) }}h</span>
        </div>
    </div>

    @if ($this->logs->isEmpty())
        <x-empty-state message="{{ __('No time logs for this month yet.') }}" />
    @else
        <div class="overflow-x-auto">
            <table class="min-w-full text-sm">
                <thead>
                    <tr class="text-left text-xs text-gray-500 border-b border-gray-200">
                        <th class="py-2 pr-4">{{ __('Date') }}</th>
                        <th class="py-2 pr-4">{{ __('Type') }}</th>
                        <th class="py-2 pr-4">{{ __('Task') }}</th>
                        <th class="py-2 pr-4">{{ __('Account') }}</th>
                        <th class="py-2 pr-4">{{ __('Duration') }}</th>
                        <th class="py-2 pr-4">{{ __('Method') }}</th>
                        <th class="py-2 pr-4">{{ __('Notes') }}</th>
                        <th class="py-2"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($this->logs as $log)
                        @if ($editingId === $log->id)
                            <tr class="border-b border-gray-100 bg-indigo-50/40">
                                <td class="py-2 pr-4" colspan="8">
                                    <form wire:submit="update" class="grid grid-cols-2 sm:grid-cols-6 gap-3 items-end">
                                        <div class="col-span-1">
                                            <label class="block text-xs text-gray-500 mb-1">{{ __('Date') }}</label>
                                            <input type="date" wire:model="edit_log_date" class="w-full rounded-md border-gray-300 text-sm">
                                        </div>
                                        <div class="col-span-1">
                                            <label class="block text-xs text-gray-500 mb-1">{{ __('Type') }}</label>
                                            <select wire:model="edit_hour_type" class="w-full rounded-md border-gray-300 text-sm">
                                                @foreach ($this->hourTypes as $type)
                                                    <option value="{{ $type->value }}">{{ $type->label() }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-span-1">
                                            <label class="block text-xs text-gray-500 mb-1">{{ __('Task') }}</label>
                                            <select wire:model="edit_task_id" class="w-full rounded-md border-gray-300 text-sm">
                                                <option value="">{{ __('No task') }}</option>
                                                @foreach ($this->tasks as $task)
                                                    <option value="{{ $task->id }}">{{ $task->title }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-span-1">
                                            <label class="block text-xs text-gray-500 mb-1">{{ __('Account') }}</label>
                                            <select wire:model="edit_account_id" class="w-full rounded-md border-gray-300 text-sm">
                                                <option value="">{{ __('No account') }}</option>
                                                @foreach ($this->accounts as $account)
                                                    <option value="{{ $account->id }}">{{ $account->name }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-span-1">
                                            <label class="block text-xs text-gray-500 mb-1">{{ __('Minutes') }}</label>
                                            <input type="number" min="1" max="1440" wire:model="edit_duration_minutes" class="w-full rounded-md border-gray-300 text-sm">
                                        </div>
                                        <div class="col-span-1 flex gap-2">
                                            <button type="submit" class="px-3 py-2 rounded-md text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700">{{ __('Save') }}</button>
                                            <button type="button" wire:click="cancelEdit" class="px-3 py-2 rounded-md text-sm font-medium text-gray-700 bg-gray-100 hover:bg-gray-200">{{ __('Cancel') }}</button>
                                        </div>
                                    </form>
                                </td>
                            </tr>
                        @else
                            <tr class="border-b border-gray-100">
                                <td class="py-2 pr-4">{{ $log->log_date->format('M j, Y') }}</td>
                                <td class="py-2 pr-4">{{ $log->hour_type->label() }}</td>
                                <td class="py-2 pr-4">{{ $log->task?->title ?? '—' }}</td>
                                <td class="py-2 pr-4">{{ $log->account?->name ?? '—' }}</td>
                                <td class="py-2 pr-4">{{ number_format($log->duration_minutes / 60, 2) }}h</td>
                                <td class="py-2 pr-4">{{ $log->entry_method->label() }}</td>
                                <td class="py-2 pr-4 text-gray-500">{{ $log->notes ?? '—' }}</td>
                                <td class="py-2 text-right whitespace-nowrap">
                                    @if ($this->canEdit($log))
                                        <button wire:click="edit({{ $log->id }})" class="text-indigo-600 hover:text-indigo-800 text-xs font-medium mr-3">{{ __('Edit') }}</button>
                                        <button wire:click="delete({{ $log->id }})" wire:confirm="{{ __('Delete this time log?') }}" class="text-rose-600 hover:text-rose-800 text-xs font-medium">{{ __('Delete') }}</button>
                                    @else
                                        <span class="text-xs text-gray-400">{{ __('Locked') }}</span>
                                    @endif
                                </td>
                            </tr>
                        @endif
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
</div>
