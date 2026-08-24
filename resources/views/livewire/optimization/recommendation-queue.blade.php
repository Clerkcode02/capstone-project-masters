<div class="space-y-4">
    @if (session('status'))
        <div class="rounded-md bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3 text-sm">
            {{ session('status') }}
        </div>
    @endif

    @if (session('error'))
        <div class="rounded-md bg-rose-50 border border-rose-200 text-rose-700 px-4 py-3 text-sm">
            {{ session('error') }}
        </div>
    @endif

    @forelse ($this->pendingRecommendations as $recommendation)
        <div wire:key="recommendation-{{ $recommendation->id }}" class="bg-white border border-gray-200 rounded-lg shadow-sm p-5 space-y-3">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-xs font-medium uppercase tracking-wide text-gray-400">
                        {{ $recommendation->task->reference }}
                        &middot;
                        <span class="{{ $recommendation->trigger_type->value === 'bottleneck' ? 'text-rose-600' : 'text-amber-600' }}">
                            {{ $recommendation->trigger_type->label() }}
                        </span>
                    </p>
                    <h3 class="text-base font-semibold text-gray-900">{{ $recommendation->task->title }}</h3>
                    <p class="text-sm text-gray-500">
                        Currently assigned to <span class="font-medium text-gray-700">{{ $recommendation->fromUser->full_name }}</span>
                    </p>
                </div>
            </div>

            <div class="text-sm text-gray-600 bg-gray-50 rounded-md px-3 py-2">
                Actual {{ number_format((float) $recommendation->actual_hours, 2) }}h
                @if ($recommendation->historical_avg_hours !== null)
                    vs. historical average {{ number_format((float) $recommendation->historical_avg_hours, 2) }}h
                @endif
                @if ($recommendation->variance_percentage !== null)
                    &mdash; <span class="font-medium">{{ number_format((float) $recommendation->variance_percentage, 1) }}% variance</span>
                @endif
            </div>

            @if ($recommendation->suggestedUser)
                <div class="flex items-center justify-between text-sm bg-sky-50 border border-sky-100 rounded-md px-3 py-2">
                    <span class="text-sky-800">
                        Suggested: <span class="font-medium">{{ $recommendation->suggestedUser->full_name }}</span>
                        ({{ $recommendation->suggested_workload_score }} workload points)
                    </span>
                </div>
            @else
                <div class="text-sm bg-slate-50 border border-slate-100 rounded-md px-3 py-2 text-slate-500">
                    No suggested assignee &mdash; this recommendation can only be dismissed.
                </div>
            @endif

            <p class="text-sm text-gray-700">{{ $recommendation->reason }}</p>

            <div class="flex items-center gap-3 pt-2">
                @if ($recommendation->suggestedUser)
                    <x-primary-button wire:click="confirmAccept({{ $recommendation->id }})">
                        {{ __('Accept & Reassign') }}
                    </x-primary-button>
                @endif
                <x-danger-button wire:click="confirmDismiss({{ $recommendation->id }})">
                    {{ __('Dismiss') }}
                </x-danger-button>
            </div>
        </div>
    @empty
        <div class="bg-white border border-gray-200 rounded-lg shadow-sm p-8 text-center text-sm text-gray-500">
            No pending recommendations right now.
        </div>
    @endforelse

    {{-- Accept confirmation --}}
    <x-modal name="confirm-accept-recommendation" :show="$confirmingAcceptId !== null" maxWidth="md">
        <div class="p-6">
            <h2 class="text-lg font-medium text-gray-900">
                {{ __('Confirm reassignment') }}
            </h2>
            <p class="mt-1 text-sm text-gray-600">
                {{ __('This reassigns the task and cannot be undone from here. Please state why.') }}
            </p>

            <div class="mt-4">
                <x-input-label for="reason" value="{{ __('Reason (required)') }}" />
                <textarea
                    id="reason"
                    wire:model="reason"
                    rows="3"
                    class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm"
                    placeholder="{{ __('e.g. Juan is over threshold and Maria has spare capacity this month.') }}"
                ></textarea>
                <x-input-error :messages="$errors->get('reason')" class="mt-2" />
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <x-secondary-button wire:click="cancel">
                    {{ __('Cancel') }}
                </x-secondary-button>
                <x-primary-button wire:click="accept" wire:loading.attr="disabled">
                    {{ __('Accept & Reassign') }}
                </x-primary-button>
            </div>
        </div>
    </x-modal>

    {{-- Dismiss confirmation --}}
    <x-modal name="confirm-dismiss-recommendation" :show="$confirmingDismissId !== null" maxWidth="md">
        <div class="p-6">
            <h2 class="text-lg font-medium text-gray-900">
                {{ __('Dismiss recommendation?') }}
            </h2>
            <p class="mt-1 text-sm text-gray-600">
                {{ __('The task assignment will not change. This only closes out the recommendation.') }}
            </p>

            <div class="mt-6 flex justify-end gap-3">
                <x-secondary-button wire:click="cancel">
                    {{ __('Cancel') }}
                </x-secondary-button>
                <x-danger-button wire:click="dismiss" wire:loading.attr="disabled">
                    {{ __('Dismiss') }}
                </x-danger-button>
            </div>
        </div>
    </x-modal>
</div>
