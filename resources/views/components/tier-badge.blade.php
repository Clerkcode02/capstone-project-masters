@props(['tier', 'percentage' => null])

@php
    $tierValue = $tier instanceof \App\Domain\Capacity\Enums\PerformanceTier ? $tier->value : $tier;

    $label = match ($tierValue) {
        'below' => 'Below Target',
        'acceptable' => 'Acceptable',
        'over' => 'Over Target',
        'not_applicable' => 'Not Applicable',
        default => ucfirst((string) $tierValue),
    };

    $classes = match ($tierValue) {
        'below' => 'bg-amber-50 text-amber-700 border-amber-200',
        'acceptable' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
        'over' => 'bg-rose-50 text-rose-700 border-rose-200',
        'not_applicable' => 'bg-slate-50 text-slate-500 border-slate-200',
        default => 'bg-slate-50 text-slate-500 border-slate-200',
    };
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center gap-1.5 rounded-full border px-2.5 py-1 text-xs font-medium {$classes}"]) }}>
    <span class="h-1.5 w-1.5 rounded-full bg-current"></span>
    <span>{{ $label }}</span>
    @if (! is_null($percentage))
        <span class="font-semibold">{{ number_format((float) $percentage, 2) }}%</span>
    @endif
</span>
