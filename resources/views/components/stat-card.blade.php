@props(['label', 'value', 'sublabel' => null, 'tone' => 'default'])

@php
    $lampClasses = match ($tone) {
        'below' => 'bg-board-amber',
        'over' => 'bg-board-red',
        'acceptable' => 'bg-board-lit',
        default => 'bg-board-line',
    };

    $valueClasses = match ($tone) {
        'below' => 'text-board-amber',
        'over' => 'text-board-red',
        default => 'text-board-text',
    };
@endphp

<div {{ $attributes->merge(['class' => 'px-4 py-3']) }}>
    <div class="flex items-center gap-1.5">
        <span class="h-1.5 w-1.5 shrink-0 rounded-full {{ $lampClasses }}"></span>
        <p class="font-board text-[0.65rem] font-semibold uppercase tracking-[0.1em] text-board-muted">{{ $label }}</p>
    </div>
    <p class="tabular-nums mt-1.5 font-board text-3xl font-extrabold leading-none {{ $valueClasses }}">{{ $value }}</p>
    @if ($sublabel)
        <p class="mt-1.5 text-[0.7rem] leading-snug text-board-muted">{{ $sublabel }}</p>
    @endif
</div>
