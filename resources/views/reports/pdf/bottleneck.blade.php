<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        @include('reports.pdf.partials.styles')
    </style>
</head>
<body>
    <h1>Bottleneck Report</h1>
    <p class="subtitle">
        Generated {{ now()->format('Y-m-d H:i') }}
        @if ($fromDate || $toDate)
            &middot; {{ $fromDate ?? 'earliest' }} to {{ $toDate ?? 'latest' }}
        @endif
    </p>

    @if ($recommendations->isEmpty())
        <p class="empty">No bottleneck-triggered recommendations recorded.</p>
    @else
        <table>
            <thead>
                <tr>
                    <th>Task</th>
                    <th>Account</th>
                    <th>From</th>
                    <th>Suggested</th>
                    <th>Actual Hrs</th>
                    <th>Historical Avg</th>
                    <th>Variance %</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($recommendations as $rec)
                    <tr>
                        <td>{{ $rec->task?->reference }} — {{ $rec->task?->title }}</td>
                        <td>{{ $rec->task?->account?->name ?? '—' }}</td>
                        <td>{{ $rec->fromUser?->full_name ?? '—' }}</td>
                        <td>{{ $rec->suggestedUser?->full_name ?? '—' }}</td>
                        <td>{{ number_format((float) $rec->actual_hours, 2) }}h</td>
                        <td>{{ is_null($rec->historical_avg_hours) ? '—' : number_format((float) $rec->historical_avg_hours, 2).'h' }}</td>
                        <td>{{ is_null($rec->variance_percentage) ? '—' : number_format((float) $rec->variance_percentage, 2).'%' }}</td>
                        <td>{{ $rec->status->label() }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <p class="footnote">This report is read-only: no recommendation here has modified tasks.assigned_to. Reassignment happens only after a manager explicitly accepts.</p>
</body>
</html>
