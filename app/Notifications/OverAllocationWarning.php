<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

class OverAllocationWarning extends Notification
{
    public function __construct(
        public readonly int $userId,
        public readonly string $userName,
        public readonly int $workloadScore,
        public readonly int $threshold,
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
            'user_id' => $this->userId,
            'user_name' => $this->userName,
            'workload_score' => $this->workloadScore,
            'threshold' => $this->threshold,
        ];
    }
}
