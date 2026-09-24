<?php

namespace App\Listeners;

use App\Domain\Identity\Enums\Role;
use App\Events\OverAllocationDetected;
use App\Models\User;
use App\Notifications\OverAllocationWarning;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification as NotificationFacade;

class NotifyOverAllocation
{
    public function handle(OverAllocationDetected $event): void
    {
        $recipients = $this->recipientsFor($event->user);

        if ($recipients->isEmpty()) {
            return;
        }

        NotificationFacade::send($recipients, new OverAllocationWarning(
            $event->user->id,
            $event->user->full_name,
            $event->workloadScore,
            $event->threshold,
        ));
    }

    /**
     * @return Collection<int, User>
     */
    private function recipientsFor(User $user): Collection
    {
        if ($user->manager_id !== null) {
            return User::query()->whereKey($user->manager_id)->where('is_active', true)->get();
        }

        return User::query()
            ->whereHas('role', fn ($query) => $query->where('name', Role::Administrator->value))
            ->where('is_active', true)
            ->get();
    }
}
