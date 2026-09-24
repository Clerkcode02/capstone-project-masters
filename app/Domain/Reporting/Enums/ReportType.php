<?php

namespace App\Domain\Reporting\Enums;

enum ReportType: string
{
    case MonthlyCapacity = 'monthly_capacity';
    case QuarterlyCapacity = 'quarterly_capacity';
    case TeamWorkload = 'team_workload';
    case Bottleneck = 'bottleneck';
    case TimeLog = 'time_log';
    case AuditLog = 'audit_log';

    public function label(): string
    {
        return match ($this) {
            self::MonthlyCapacity => 'Monthly Capacity Report',
            self::QuarterlyCapacity => 'Quarterly Capacity Report',
            self::TeamWorkload => 'Team Workload Distribution',
            self::Bottleneck => 'Bottleneck Report',
            self::TimeLog => 'Time Log Report',
            self::AuditLog => 'Audit Log Export',
        };
    }
}
