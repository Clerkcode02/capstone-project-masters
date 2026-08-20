<div>
    <x-modal :name="'assign-task-'.$task->id" focusable>
        <div class="p-6">
            <h2 class="text-lg font-semibold text-gray-900">
                Assign &ldquo;{{ $task->title }}&rdquo;
            </h2>
            <p class="mt-1 text-sm text-gray-500">
                {{ $task->complexity_tier->label() }} task &middot; {{ $task->complexity_weight }} workload point(s)
            </p>

            <div class="mt-4 space-y-2">
                @forelse ($this->candidates as $candidate)
                    <button
                        type="button"
                        wire:click="selectCandidate({{ $candidate->id }})"
                        class="w-full flex items-center justify-between rounded-md border px-3 py-2 text-left text-sm
                            {{ $selectedCandidateId === $candidate->id ? 'border-sky-400 bg-sky-50' : 'border-gray-200 hover:bg-gray-50' }}"
                    >
                        <span>{{ $candidate->full_name }}</span>
                        <span class="text-xs text-gray-400">{{ $candidate->designation?->name }}</span>
                    </button>
                @empty
                    <x-empty-state message="No active team members are assigned to this account." />
                @endforelse
            </div>

            @if ($showWarning && $this->check)
                <div class="mt-4 rounded-md border border-rose-200 bg-rose-50 p-4 text-sm text-rose-700">
                    <p class="font-semibold">&#9888; Workload warning</p>
                    <p class="mt-1">
                        {{ $this->check->candidate->full_name }} is at {{ $this->check->currentScore }} of {{ $this->check->threshold }} workload points.
                        Adding this {{ $task->complexity_tier->label() }} task ({{ $task->complexity_weight }} point(s)) puts them at
                        {{ $this->check->prospectiveScore }} &mdash; {{ $this->check->percentOverThreshold }}% over threshold.
                    </p>

                    @if (count($this->check->alternatives) > 0)
                        <p class="mt-3 font-semibold">Suggested alternatives</p>
                        <ul class="mt-1 space-y-1">
                            @foreach ($this->check->alternatives as $alternative)
                                <li class="flex items-center justify-between">
                                    <span>
                                        {{ $alternative->user->full_name }}
                                        &middot; {{ $alternative->workloadScore }} / {{ $this->check->threshold }}
                                        @if ($alternative->remainingHours !== null)
                                            &middot; {{ number_format($alternative->remainingHours, 1) }}h remaining capacity
                                        @endif
                                    </span>
                                    <button
                                        type="button"
                                        wire:click="selectCandidate({{ $alternative->user->id }})"
                                        class="text-xs font-semibold text-sky-600 hover:text-sky-700"
                                    >
                                        Assign
                                    </button>
                                </li>
                            @endforeach
                        </ul>
                    @endif

                    <div class="mt-4 flex gap-2">
                        <x-danger-button wire:click="confirmAssign(true)">
                            Assign to {{ $this->check->candidate->full_name }} anyway
                        </x-danger-button>
                        <x-secondary-button wire:click="cancel">Cancel</x-secondary-button>
                    </div>
                </div>
            @elseif ($selectedCandidateId)
                <div class="mt-4 flex justify-end gap-2">
                    <x-secondary-button wire:click="cancel">Cancel</x-secondary-button>
                    <x-primary-button wire:click="confirmAssign">Assign</x-primary-button>
                </div>
            @endif
        </div>
    </x-modal>
</div>
