<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

class NewRecommendationAvailable extends Notification
{
    public function __construct(
        public readonly int $recommendationId,
        public readonly string $taskReference,
        public readonly string $triggerLabel,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'recommendation_id' => $this->recommendationId,
            'task_reference' => $this->taskReference,
            'trigger_label' => $this->triggerLabel,
        ];
    }
}
