<?php

namespace App\Listeners;

use App\Events\ReportExported;
use App\Models\AuditLog;

class LogReportExported
{
    public function handle(ReportExported $event): void
    {
        AuditLog::create([
            'user_id' => $event->exportedBy->id,
            'action' => 'report_exported',
            'description' => "Exported {$event->reportType->label()} ({$event->format}).",
        ]);
    }
}
