<?php

namespace App\Models;

use App\Domain\Optimization\Enums\BottleneckBasis;
use App\Domain\Optimization\Enums\RecommendationStatus;
use App\Domain\Optimization\Enums\TriggerType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RedistributionRecommendation extends Model
{
    protected $fillable = [
        'task_id',
        'from_user_id',
        'suggested_user_id',
        'trigger_type',
        'actual_hours',
        'historical_avg_hours',
        'variance_percentage',
        'from_workload_score',
        'suggested_workload_score',
        'basis',
        'reason',
        'status',
        'reviewed_by',
        'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'trigger_type' => TriggerType::class,
            'actual_hours' => 'decimal:2',
            'historical_avg_hours' => 'decimal:2',
            'variance_percentage' => 'decimal:2',
            'from_workload_score' => 'integer',
            'suggested_workload_score' => 'integer',
            'basis' => BottleneckBasis::class,
            'status' => RecommendationStatus::class,
            'reviewed_at' => 'datetime',
        ];
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    public function fromUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'from_user_id');
    }

    public function suggestedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'suggested_user_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function taskReassignments(): HasMany
    {
        return $this->hasMany(TaskReassignment::class, 'recommendation_id');
    }
}
