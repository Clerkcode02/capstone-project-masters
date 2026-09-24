<?php

namespace App\Providers;

use App\Models\AuditLog;
use App\Models\CapacityMetric;
use App\Models\RedistributionRecommendation;
use App\Models\Task;
use App\Models\TimeLog;
use App\Models\User;
use App\Policies\AuditLogPolicy;
use App\Policies\CapacityMetricPolicy;
use App\Policies\RecommendationPolicy;
use App\Policies\TaskPolicy;
use App\Policies\TimeLogPolicy;
use App\Policies\UserPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * @var array<class-string, class-string>
     */
    protected $policies = [
        User::class => UserPolicy::class,
        Task::class => TaskPolicy::class,
        TimeLog::class => TimeLogPolicy::class,
        CapacityMetric::class => CapacityMetricPolicy::class,
        RedistributionRecommendation::class => RecommendationPolicy::class,
        AuditLog::class => AuditLogPolicy::class,
    ];

    public function boot(): void
    {
        $this->registerPolicies();
    }
}
