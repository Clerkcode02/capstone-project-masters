@props(['score', 'threshold', 'tier' => null, 'label' => null, 'segments' => 12])

@php
    $score = (float) $score;
    $threshold = (float) $threshold;
    $ratio = $threshold > 0 ? $score / $threshold : 0;
    $filled = (int) round(min(max($ratio, 0), 1) * $segments);

    $tierValue = $tier instanceof \App\Domain\Capacity\Enums\PerformanceTier ? $tier->value : $tier;

    // color reads from the same tier state as x-tier-badge, never its own ratio math,
    // so the gauge and the lamp never disagree about what "on target" means
    $segmentColor = match ($tierValue) {
        'below' => 'bg-board-amber',
        'over' => 'bg-board-red',
        default => 'bg-board-lit',
    };
@endphp

<div {{ $attributes }}>
    <div class="hidden items-center justify-between font-board text-[0.65rem] uppercase tracking-[0.08em] text-board-muted sm:flex">
        <span>{{ $label ?? 'Hours used' }}</span>
        <span class="tabular-nums font-slip normal-case tracking-normal text-board-text">{{ rtrim(rtrim(number_format($score, 2), '0'), '.') }} / {{ rtrim(rtrim(number_format($threshold, 2), '0'), '.') }}h</span>
    </div>
    <div class="mt-1.5 flex gap-[3px]" role="img" aria-label="{{ number_format(min(max($ratio, 0), 1) * 100, 0) }}% of capacity used">
        @for ($i = 0; $i < $segments; $i++)
            <span class="h-2.5 flex-1 rounded-[1px] {{ $i < $filled ? $segmentColor : 'bg-board-line' }}"></span>
        @endfor
    </div>
</div>
