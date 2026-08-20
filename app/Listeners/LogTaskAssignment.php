<?php

namespace App\Listeners;

use App\Events\TaskAssigned;
use App\Models\AuditLog;

class LogTaskAssignment
{
    public function handle(TaskAssigned $event): void
    {
        AuditLog::create([
            'user_id' => $event->actor->id,
            'action' => $event->wasOverAllocationOverride ? 'over_allocation_override' : 'task_assigned',
            'auditable_type' => $event->task::class,
            'auditable_id' => $event->task->id,
            'description' => $event->wasOverAllocationOverride
                ? "{$event->actor->full_name} assigned task {$event->task->reference} to {$event->assignee->full_name}, overriding a workload warning."
                : "{$event->actor->full_name} assigned task {$event->task->reference} to {$event->assignee->full_name}.",
        ]);
    }
}
