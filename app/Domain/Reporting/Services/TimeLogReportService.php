<?php

namespace App\Domain\Reporting\Services;

use App\Domain\Identity\Enums\Role;
use App\Models\TimeLog;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Time log export. Employees can never read another employee's raw time
 * logs — scoped via TimeLog::visibleTo(), then re-forced to self for an
 * employee actor even if a stray user_id filter is passed.
 */
class TimeLogReportService
{
    /**
     * @return Collection<int, TimeLog>
     */
    public function build(User $actor, ?string $fromDate = null, ?string $toDate = null, ?int $userId = null): Collection
    {
        if ($actor->isRole(Role::Employee)) {
            $userId = $actor->id;
        }

        return TimeLog::query()
            ->with(['user', 'task', 'account'])
            ->visibleTo($actor)
            ->when($userId, fn ($q) => $q->where('user_id', $userId))
            ->when($fromDate, fn ($q) => $q->whereDate('log_date', '>=', $fromDate))
            ->when($toDate, fn ($q) => $q->whereDate('log_date', '<=', $toDate))
            ->orderBy('log_date')
            ->get();
    }
}
