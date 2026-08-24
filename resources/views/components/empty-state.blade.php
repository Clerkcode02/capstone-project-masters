@props(['message' => 'Nothing to show yet.', 'icon' => true])

<div {{ $attributes->merge(['class' => 'flex flex-col items-center justify-center gap-2 rounded-lg border border-dashed border-gray-200 bg-white py-12 px-6 text-center']) }}>
    @if ($icon)
        <svg class="h-8 w-8 text-gray-300" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
        </svg>
    @endif
    <p class="text-sm text-gray-500">{{ $message }}</p>
    {{ $slot ?? '' }}
</div>
