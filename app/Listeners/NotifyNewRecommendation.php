<?php

namespace App\Listeners;

use App\Domain\Identity\Enums\Role;
use App\Events\RecommendationCreated;
use App\Models\User;
use App\Notifications\NewRecommendationAvailable;
use Illuminate\Support\Facades\Notification;

class NotifyNewRecommendation
{
    public function handle(RecommendationCreated $event): void
    {
        $recommendation = $event->recommendation;

        $recipients = User::query()
            ->whereHas('role', fn ($query) => $query->whereIn('name', [Role::Manager->value, Role::Administrator->value]))
            ->where('is_active', true)
            ->get();

        if ($recipients->isEmpty()) {
            return;
        }

        Notification::send($recipients, new NewRecommendationAvailable(
            $recommendation->id,
            $recommendation->task?->reference ?? "#{$recommendation->task_id}",
            $recommendation->trigger_type->label(),
        ));
    }
}
