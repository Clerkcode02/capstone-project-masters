<?php

namespace App\Models;

use App\Domain\Identity\Enums\Role;
use App\Domain\Tasks\Enums\ComplexityTier;
use App\Domain\Tasks\Enums\TaskStatus;
use Database\Factories\TaskFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Task extends Model
{
    /** @use HasFactory<TaskFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'reference',
        'title',
        'description',
        'account_id',
        'assigned_to',
        'created_by',
        'complexity_tier',
        'complexity_weight',
        'standard_hours',
        'actual_hours',
        'status',
        'is_bottleneck',
        'due_date',
        'started_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'complexity_tier' => ComplexityTier::class,
            'complexity_weight' => 'integer',
            'standard_hours' => 'decimal:2',
            'actual_hours' => 'decimal:2',
            'status' => TaskStatus::class,
            'is_bottleneck' => 'boolean',
            'due_date' => 'date',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function timeLogs(): HasMany
    {
        return $this->hasMany(TimeLog::class);
    }

    public function reassignments(): HasMany
    {
        return $this->hasMany(TaskReassignment::class);
    }

    public function redistributionRecommendations(): HasMany
    {
        return $this->hasMany(RedistributionRecommendation::class);
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->isRole(Role::Employee)) {
            return $query->where('assigned_to', $user->id);
        }

        return $query;
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', TaskStatus::InProgress->value);
    }
}
