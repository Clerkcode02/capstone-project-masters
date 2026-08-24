@props(['tier', 'percentage' => null])

@php
    $tierValue = $tier instanceof \App\Domain\Capacity\Enums\PerformanceTier ? $tier->value : $tier;

    $label = match ($tierValue) {
        'below' => 'Below Threshold',
        'acceptable' => 'On Target',
        'over' => 'Over Threshold',
        'not_applicable' => 'Not Applicable',
        default => ucfirst((string) $tierValue),
    };

    $lampClasses = match ($tierValue) {
        'below' => 'bg-board-amber text-board-amber-ink',
        'over' => 'bg-board-red text-board-red-ink',
        default => 'bg-board-lit text-board-panel',
    };

    $textClasses = match ($tierValue) {
        'below' => 'text-board-amber',
        'over' => 'text-board-red',
        'not_applicable' => 'text-board-muted',
        default => 'text-board-text',
    };
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center gap-2 font-board {$textClasses}"]) }}>
    <span class="flex h-5 w-5 shrink-0 items-center justify-center rounded-sm {{ $lampClasses }}">
        @switch($tierValue)
            @case('below')
                {{-- under threshold: a downward mark --}}
                <svg viewBox="0 0 16 16" class="h-3 w-3" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M8 3v8M4.5 8 8 11.5 11.5 8" />
                </svg>
                @break
            @case('over')
                {{-- over threshold: risk mark --}}
                <svg viewBox="0 0 16 16" class="h-3 w-3" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M8 5v4" />
                    <circle cx="8" cy="11.25" r="0.75" fill="currentColor" stroke="none" />
                    <path d="M8 2 1.5 13.5h13Z" />
                </svg>
                @break
            @case('not_applicable')
                <svg viewBox="0 0 16 16" class="h-3 w-3" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true">
                    <path d="M4.5 8h7" />
                </svg>
                @break
            @default
                {{-- on target: a checkmark --}}
                <svg viewBox="0 0 16 16" class="h-3 w-3" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <path d="M3.5 8.5 6.5 11.5 12.5 4.5" />
                </svg>
        @endswitch
    </span>
    <span class="text-xs font-semibold uppercase tracking-[0.08em]">{{ $label }}</span>
    @if (! is_null($percentage))
        <span class="tabular-nums text-xs font-semibold">{{ number_format((float) $percentage, 1) }}%</span>
    @endif
</span>
