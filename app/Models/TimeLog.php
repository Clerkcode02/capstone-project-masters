<?php

namespace App\Models;

use App\Domain\Identity\Enums\Role;
use App\Domain\TimeTracking\Enums\EntryMethod;
use App\Domain\TimeTracking\Enums\HourType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TimeLog extends Model
{
    protected $fillable = [
        'user_id',
        'task_id',
        'account_id',
        'log_date',
        'hour_type',
        'duration_minutes',
        'entry_method',
        'started_at',
        'ended_at',
        'notes',
        'is_locked',
    ];

    protected function casts(): array
    {
        return [
            'log_date' => 'date',
            'hour_type' => HourType::class,
            'duration_minutes' => 'integer',
            'entry_method' => EntryMethod::class,
            'started_at' => 'datetime',
            'ended_at' => 'datetime',
            'is_locked' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->isRole(Role::Employee)) {
            return $query->where('user_id', $user->id);
        }

        return $query;
    }
}
