<?php

namespace App\Domain\Administration\Services;

use App\Models\AuditLog;

class AuditLogService
{
    public function log(
        ?int $userId,
        string $action,
        ?string $auditableType = null,
        ?int $auditableId = null,
        ?string $description = null,
        ?string $ipAddress = null,
        ?string $userAgent = null,
    ): AuditLog {
        return AuditLog::create([
            'user_id' => $userId,
            'action' => $action,
            'auditable_type' => $auditableType,
            'auditable_id' => $auditableId,
            'description' => $description,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
        ]);
    }
}
