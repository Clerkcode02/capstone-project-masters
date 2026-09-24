<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        @include('reports.pdf.partials.styles')
    </style>
</head>
<body>
    <h1>Quarterly Capacity Report</h1>
    <p class="subtitle">Q{{ $quarter }} {{ $year }} &middot; Generated {{ now()->format('Y-m-d H:i') }}</p>

    @if ($rows->isEmpty())
        <p class="empty">No capacity metrics recorded for this quarter.</p>
    @else
        <table>
            <thead>
                <tr>
                    <th>Employee</th>
                    @foreach ($months as $m)
                        <th>{{ \Carbon\Carbon::create()->month($m)->format('F') }}</th>
                    @endforeach
                    <th>Quarterly</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($rows as $row)
                    <tr>
                        <td>{{ $row['user']?->full_name ?? 'Unknown' }}</td>
                        @foreach ($months as $m)
                            @php $metric = $row['months'][$m] ?? null; @endphp
                            <td>
                                @if ($metric)
                                    @include('reports.pdf.partials.tier-badge', ['tier' => $metric->performance_tier->value, 'percentage' => $metric->performance_percentage])
                                @else
                                    —
                                @endif
                            </td>
                        @endforeach
                        <td>
                            @if (! is_null($row['quarterly_percentage']))
                                @include('reports.pdf.partials.tier-badge', ['tier' => $row['quarterly_tier']->value, 'percentage' => $row['quarterly_percentage']])
                            @else
                                —
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <p class="footnote">Quarterly Performance % = arithmetic mean of the three monthly percentages.</p>
</body>
</html>
