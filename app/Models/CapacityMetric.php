<?php

namespace App\Models;

use App\Domain\Capacity\Enums\PerformanceTier;
use App\Domain\Identity\Enums\Role;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CapacityMetric extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'period_type',
        'period_year',
        'period_month',
        'period_quarter',
        'h_base',
        'h_leave',
        'h_poss',
        'u_target',
        'h_thresh',
        'h_prod',
        'h_non_prod',
        'performance_percentage',
        'performance_tier',
        'effective_availability_hours',
        'computed_at',
    ];

    protected function casts(): array
    {
        return [
            'period_year' => 'integer',
            'period_month' => 'integer',
            'period_quarter' => 'integer',
            'h_base' => 'decimal:2',
            'h_leave' => 'decimal:2',
            'h_poss' => 'decimal:2',
            'u_target' => 'decimal:3',
            'h_thresh' => 'decimal:2',
            'h_prod' => 'decimal:2',
            'h_non_prod' => 'decimal:2',
            'performance_percentage' => 'decimal:2',
            'performance_tier' => PerformanceTier::class,
            'effective_availability_hours' => 'decimal:2',
            'computed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeVisibleTo(Builder $query, User $user): Builder
    {
        if ($user->isRole(Role::Employee)) {
            return $query->where('user_id', $user->id);
        }

        return $query;
    }
}
