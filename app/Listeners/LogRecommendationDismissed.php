<?php

namespace App\Listeners;

use App\Events\RecommendationDismissed;
use App\Models\AuditLog;

class LogRecommendationDismissed
{
    public function handle(RecommendationDismissed $event): void
    {
        AuditLog::create([
            'user_id' => $event->reviewedBy->id,
            'action' => 'recommendation_dismissed',
            'auditable_type' => $event->recommendation::class,
            'auditable_id' => $event->recommendation->id,
            'description' => "Dismissed recommendation for task #{$event->recommendation->task_id}.",
        ]);
    }
}
