<?php

namespace App\Listeners;

use App\Events\RecommendationAccepted;
use App\Models\AuditLog;

class LogRecommendationAccepted
{
    public function handle(RecommendationAccepted $event): void
    {
        AuditLog::create([
            'user_id' => $event->reviewedBy->id,
            'action' => 'recommendation_accepted',
            'auditable_type' => $event->recommendation::class,
            'auditable_id' => $event->recommendation->id,
            'description' => "Accepted recommendation for task #{$event->recommendation->task_id}: reassigned from user #{$event->reassignment->from_user_id} to user #{$event->reassignment->to_user_id}. Reason: {$event->reassignment->reason}",
        ]);
    }
}
