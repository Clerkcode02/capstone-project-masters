<?php

namespace App\Domain\Reporting\Services;

use App\Models\AuditLog;
use Illuminate\Support\Collection;

/**
 * Audit trail export. Administrator-only — gated by AuditLogPolicy, not here.
 */
class AuditLogReportService
{
    /**
     * @return Collection<int, AuditLog>
     */
    public function build(?string $fromDate = null, ?string $toDate = null, ?string $action = null): Collection
    {
        return AuditLog::query()
            ->with('user')
            ->when($fromDate, fn ($q) => $q->whereDate('created_at', '>=', $fromDate))
            ->when($toDate, fn ($q) => $q->whereDate('created_at', '<=', $toDate))
            ->when($action, fn ($q) => $q->where('action', $action))
            ->orderByDesc('created_at')
            ->get();
    }
}
