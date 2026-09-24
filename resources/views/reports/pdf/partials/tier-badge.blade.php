@php
    $label = match ($tier) {
        'below' => 'Below Target',
        'acceptable' => 'Acceptable',
        'over' => 'Over Target',
        'not_applicable' => 'Not Applicable',
        default => ucfirst($tier),
    };

    $colors = match ($tier) {
        'below' => ['#fffbeb', '#b45309', '#fde68a'],
        'acceptable' => ['#ecfdf5', '#047857', '#a7f3d0'],
        'over' => ['#fff1f2', '#be123c', '#fecdd3'],
        default => ['#f8fafc', '#475569', '#e2e8f0'],
    };
@endphp
<span class="badge" style="background-color: {{ $colors[0] }}; color: {{ $colors[1] }}; border-color: {{ $colors[2] }};">
    {{ $label }}@if(isset($percentage) && ! is_null($percentage)) &middot; {{ number_format((float) $percentage, 2) }}%@endif
</span>
