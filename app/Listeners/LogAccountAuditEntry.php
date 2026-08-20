<?php

namespace App\Listeners;

use App\Domain\Accounts\Events\AccountAudited;
use App\Domain\Administration\Services\AuditLogger;

class LogAccountAuditEntry
{
    public function __construct(private readonly AuditLogger $auditLogger) {}

    public function handle(AccountAudited $event): void
    {
        $this->auditLogger->record($event->actor, $event->action, $event->account, $event->description);
    }
}
