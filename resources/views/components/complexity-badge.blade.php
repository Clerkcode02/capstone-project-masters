@props(['tier'])

@php
    $tierValue = $tier instanceof \App\Domain\Tasks\Enums\ComplexityTier ? $tier->value : $tier;

    $label = match ($tierValue) {
        'small' => 'Small',
        'medium' => 'Medium',
        'large' => 'Large',
        default => ucfirst((string) $tierValue),
    };

    $classes = match ($tierValue) {
        'small' => 'bg-slate-50 text-slate-700 border-slate-200',
        'medium' => 'bg-sky-50 text-sky-700 border-sky-200',
        'large' => 'bg-violet-50 text-violet-700 border-violet-200',
        default => 'bg-slate-50 text-slate-500 border-slate-200',
    };
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center rounded-full border px-2.5 py-1 text-xs font-medium {$classes}"]) }}>
    {{ $label }}
</span>
