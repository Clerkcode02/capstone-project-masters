<div class="bg-white shadow-sm rounded-lg p-6">
    <h3 class="text-lg font-semibold text-gray-800 mb-4">Create &amp; assign task</h3>

    <form wire:submit="save" class="space-y-4">
        <div>
            <label class="block text-sm font-medium text-gray-700">Title</label>
            <input type="text" wire:model="form.title" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
            @error('form.title') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
        </div>

        <div>
            <label class="block text-sm font-medium text-gray-700">Description (optional)</label>
            <textarea wire:model="form.description" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm" rows="2"></textarea>
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700">Complexity</label>
                <select wire:model="form.complexity_tier" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                    <option value="small">Small</option>
                    <option value="medium">Medium</option>
                    <option value="large">Large</option>
                </select>
                @error('form.complexity_tier') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Standard hours</label>
                <input type="number" step="0.25" wire:model="form.standard_hours" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                @error('form.standard_hours') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>
        </div>

        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-gray-700">Due date (optional)</label>
                <input type="date" wire:model="form.due_date" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                @error('form.due_date') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700">Assign to</label>
                <select wire:model="form.assigned_to" class="mt-1 block w-full border-gray-300 rounded-md shadow-sm">
                    <option value="">Select an employee</option>
                    @foreach ($this->employees as $employee)
                        <option value="{{ $employee->id }}">{{ $employee->full_name }}</option>
                    @endforeach
                </select>
                @error('form.assigned_to') <p class="text-sm text-red-600 mt-1">{{ $message }}</p> @enderror
            </div>
        </div>

        @if ($confirmingOverAllocation)
            <div class="rounded-md border border-amber-300 bg-amber-50 p-4 space-y-3" role="alert">
                <p class="text-sm font-semibold text-amber-800">
                    This assignment would push the employee over the workload threshold.
                </p>

                <x-workload-bar :score="$evaluationProspectiveScore" :threshold="$evaluationThreshold" label="Workload after this task" />

                <p class="text-xs text-amber-700">
                    Current workload: {{ $evaluationCurrentScore }} of {{ $evaluationThreshold }}.
                    With this task: {{ $evaluationProspectiveScore }} of {{ $evaluationThreshold }}.
                </p>

                @if (! empty($alternatives))
                    <div>
                        <p class="text-xs font-medium text-amber-800 mb-1">Employees with more spare capacity on this account:</p>
                        <ul class="text-xs text-amber-700 list-disc list-inside">
                            @foreach ($alternatives as $alternative)
                                <li>{{ $alternative['name'] }} — {{ $alternative['score'] }} of {{ $evaluationThreshold }} workload points</li>
                            @endforeach
                        </ul>
                    </div>
                @else
                    <p class="text-xs text-amber-700">No other employee on this account currently has spare capacity.</p>
                @endif

                <div class="flex items-center gap-3 pt-1">
                    <button
                        type="submit"
                        class="inline-flex items-center px-3 py-1.5 bg-amber-600 text-white text-xs font-medium rounded-md hover:bg-amber-700"
                    >
                        Assign anyway
                    </button>
                    <button
                        type="button"
                        wire:click="cancelOverAllocation"
                        class="inline-flex items-center px-3 py-1.5 bg-white text-amber-700 text-xs font-medium rounded-md border border-amber-300 hover:bg-amber-100"
                    >
                        Choose a different employee
                    </button>
                </div>
            </div>
        @else
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
                <span wire:loading.remove wire:target="save">Create task</span>
                <span wire:loading wire:target="save">Assigning&hellip;</span>
            </button>
        @endif
    </form>
</div>
