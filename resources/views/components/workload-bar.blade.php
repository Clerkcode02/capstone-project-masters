@props(['score', 'threshold', 'label' => null])

@php
    $score = (float) $score;
    $threshold = (float) $threshold;
    $ratio = $threshold > 0 ? $score / $threshold : 0;
    $widthPct = (int) round(min($ratio, 1) * 100);

    $barColor = match (true) {
        $ratio > 1 => 'bg-rose-500',
        $ratio >= 0.9 => 'bg-amber-500',
        default => 'bg-emerald-500',
    };
@endphp

<div {{ $attributes }}>
    <div class="flex items-center justify-between text-xs text-gray-500">
        <span>{{ $label ?? 'Workload' }}</span>
        <span class="font-medium text-gray-700">{{ rtrim(rtrim(number_format($score, 2), '0'), '.') }} / {{ rtrim(rtrim(number_format($threshold, 2), '0'), '.') }}</span>
    </div>
    <div class="mt-1 h-2 w-full overflow-hidden rounded-full bg-gray-100">
        <div class="h-full rounded-full {{ $barColor }}" style="width: {{ $widthPct }}%"></div>
    </div>
</div>
