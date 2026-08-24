@props(['label', 'value', 'sublabel' => null, 'tone' => 'default'])

@php
    $toneClasses = match ($tone) {
        'below' => 'border-amber-200',
        'acceptable' => 'border-emerald-200',
        'over' => 'border-rose-200',
        default => 'border-gray-200',
    };
@endphp

<div {{ $attributes->merge(['class' => "rounded-lg border {$toneClasses} bg-white p-5 shadow-sm"]) }}>
    <p class="text-sm font-medium text-gray-500">{{ $label }}</p>
    <p class="mt-1 text-3xl font-semibold text-gray-900">{{ $value }}</p>
    @if ($sublabel)
        <p class="mt-1 text-xs text-gray-400">{{ $sublabel }}</p>
    @endif
</div>
