@props(['message' => 'Nothing to show yet.', 'icon' => true])

<div {{ $attributes->merge(['class' => 'flex flex-col items-center justify-center gap-3 rounded-sm border border-dashed border-board-line bg-board-panel py-14 px-6 text-center']) }}>
    @if ($icon)
        {{-- an unlit flap row: the board's own vocabulary for "nothing logged" --}}
        <svg viewBox="0 0 40 28" class="h-7 w-10 text-board-line" fill="none" aria-hidden="true">
            <rect x="0.75" y="0.75" width="38.5" height="7.5" rx="1" stroke="currentColor" stroke-width="1.5" />
            <rect x="0.75" y="10.25" width="38.5" height="7.5" rx="1" stroke="currentColor" stroke-width="1.5" />
            <rect x="0.75" y="19.75" width="38.5" height="7.5" rx="1" stroke="currentColor" stroke-width="1.5" />
        </svg>
    @endif
    <p class="max-w-xs font-board text-sm text-board-muted">{{ $message }}</p>
    {{ $slot ?? '' }}
</div>
