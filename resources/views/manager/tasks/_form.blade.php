@php
    /** @var \App\Models\Task|null $task */
    $task ??= null;
@endphp

<div>
    <x-input-label for="title" :value="__('Title')" />
    <x-text-input id="title" name="title" type="text" class="mt-1 block w-full"
                  value="{{ old('title', $task?->title) }}" required autofocus />
    <x-input-error :messages="$errors->get('title')" class="mt-2" />
</div>

<div class="mt-4">
    <x-input-label for="description" :value="__('Description')" />
    <textarea id="description" name="description" rows="4"
              class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">{{ old('description', $task?->description) }}</textarea>
    <x-input-error :messages="$errors->get('description')" class="mt-2" />
</div>

<div class="mt-4">
    <x-input-label for="account_id" :value="__('Account')" />
    <select id="account_id" name="account_id" required
            class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
        <option value="">{{ __('Select an account') }}</option>
        @foreach ($accounts as $account)
            <option value="{{ $account->id }}" @selected(old('account_id', $task?->account_id) == $account->id)>
                {{ $account->name }}
            </option>
        @endforeach
    </select>
    <x-input-error :messages="$errors->get('account_id')" class="mt-2" />
</div>

<div class="mt-4">
    <x-input-label for="complexity_tier" :value="__('Complexity Tier')" />
    <select id="complexity_tier" name="complexity_tier" required
            class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
        @foreach (\App\Domain\Tasks\Enums\ComplexityTier::cases() as $tier)
            <option value="{{ $tier->value }}" @selected(old('complexity_tier', $task?->complexity_tier?->value) === $tier->value)>
                {{ $tier->label() }}
            </option>
        @endforeach
    </select>
    <x-input-error :messages="$errors->get('complexity_tier')" class="mt-2" />
</div>

<div class="mt-4">
    <x-input-label for="standard_hours" :value="__('Standard Hours')" />
    <x-text-input id="standard_hours" name="standard_hours" type="number" step="0.25" min="0.25" max="999.99"
                  class="mt-1 block w-full" value="{{ old('standard_hours', $task?->standard_hours) }}" required />
    <x-input-error :messages="$errors->get('standard_hours')" class="mt-2" />
</div>

<div class="mt-4">
    <x-input-label for="due_date" :value="__('Due Date')" />
    <x-text-input id="due_date" name="due_date" type="date" class="mt-1 block w-full"
                  value="{{ old('due_date', $task?->due_date?->format('Y-m-d')) }}" />
    <x-input-error :messages="$errors->get('due_date')" class="mt-2" />
</div>
