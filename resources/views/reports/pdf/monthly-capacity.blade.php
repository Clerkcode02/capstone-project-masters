<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        @include('reports.pdf.partials.styles')
    </style>
</head>
<body>
    <h1>Monthly Capacity Report</h1>
    <p class="subtitle">{{ \Carbon\Carbon::create()->month($month)->format('F') }} {{ $year }} &middot; Generated {{ now()->format('Y-m-d H:i') }}</p>

    <div class="stat-row">
        <div class="stat"><span class="stat-value">{{ $teamSize }}</span><span class="stat-label">Team Size</span></div>
        <div class="stat"><span class="stat-value">{{ $onTargetCount }}</span><span class="stat-label">On Target</span></div>
        <div class="stat"><span class="stat-value">{{ $overUtilizedCount }}</span><span class="stat-label">Over-Utilized</span></div>
        <div class="stat"><span class="stat-value">{{ $underUtilizedCount }}</span><span class="stat-label">Under-Utilized</span></div>
    </div>

    @if ($metrics->isEmpty())
        <p class="empty">No capacity metrics recorded for this period.</p>
    @else
        <table>
            <thead>
                <tr>
                    <th>Employee</th>
                    <th>Designation</th>
                    <th>H_poss</th>
                    <th>U_target</th>
                    <th>H_thresh</th>
                    <th>H_prod</th>
                    <th>Performance %</th>
                    <th>Tier</th>
                    <th>Effective Availability</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($metrics as $metric)
                    <tr>
                        <td>{{ $metric->user?->full_name ?? 'Unknown' }}</td>
                        <td>{{ $metric->user?->designation?->name ?? '—' }}</td>
                        <td>{{ number_format((float) $metric->h_poss, 2) }}h</td>
                        <td>{{ number_format((float) $metric->u_target, 3) }}</td>
                        <td>{{ number_format((float) $metric->h_thresh, 2) }}h</td>
                        <td>{{ number_format((float) $metric->h_prod, 2) }}h</td>
                        <td>{{ is_null($metric->performance_percentage) ? 'n/a' : number_format((float) $metric->performance_percentage, 2).'%' }}</td>
                        <td>@include('reports.pdf.partials.tier-badge', ['tier' => $metric->performance_tier->value])</td>
                        <td>{{ number_format((float) $metric->effective_availability_hours, 2) }}h</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <p class="footnote">Monthly Performance % = (H_prod &divide; H_thresh) &times; 100. Effective Availability = H_thresh &minus; H_prod.</p>
</body>
</html>
