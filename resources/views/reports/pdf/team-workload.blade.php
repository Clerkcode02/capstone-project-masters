<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        @include('reports.pdf.partials.styles')
    </style>
</head>
<body>
    <h1>Team Workload Distribution</h1>
    <p class="subtitle">Generated {{ now()->format('Y-m-d H:i') }} &middot; Threshold: {{ $threshold }} points</p>

    @if ($scores->isEmpty())
        <p class="empty">No active employees to score.</p>
    @else
        <table>
            <thead>
                <tr>
                    <th>Employee</th>
                    <th>Designation</th>
                    <th>Workload Score</th>
                    <th>Threshold</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($scores as $row)
                    <tr>
                        <td>{{ $row['name'] }}</td>
                        <td>{{ $row['designation'] ?? '—' }}</td>
                        <td>{{ $row['score'] }}</td>
                        <td>{{ $threshold }}</td>
                        <td>
                            @if ($row['score'] > $threshold)
                                <span class="badge" style="background-color: #fff1f2; color: #be123c; border-color: #fecdd3;">Over Threshold</span>
                            @else
                                <span class="badge" style="background-color: #ecfdf5; color: #047857; border-color: #a7f3d0;">Within Threshold</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <p class="footnote">Workload Score = sum of complexity weights across a user's in_progress tasks.</p>
</body>
</html>
