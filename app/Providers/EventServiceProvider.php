<?php

namespace App\Providers;

use App\Domain\Accounts\Events\AccountAudited;
use App\Events\BaselineUpdated;
use App\Events\ProductionSheetImported;
use App\Events\RecommendationAccepted;
use App\Events\RecommendationDismissed;
use App\Events\RoleChanged;
use App\Events\SettingUpdated;
use App\Events\UserCreated;
use App\Events\UserUpdated;
use App\Listeners\LogAccountAuditEntry;
use App\Listeners\LogProductionSheetImported;
use App\Listeners\LogRecommendationAccepted;
use App\Listeners\LogRecommendationDismissed;
use App\Listeners\WriteAuditLog;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, string>>
     */
    protected $listen = [
        Login::class => [
            WriteAuditLog::class.'@handleLogin',
        ],
        Logout::class => [
            WriteAuditLog::class.'@handleLogout',
        ],
        Failed::class => [
            WriteAuditLog::class.'@handleFailedLogin',
        ],
        UserCreated::class => [
            WriteAuditLog::class.'@handleUserCreated',
        ],
        UserUpdated::class => [
            WriteAuditLog::class.'@handleUserUpdated',
        ],
        RoleChanged::class => [
            WriteAuditLog::class.'@handleRoleChanged',
        ],
        SettingUpdated::class => [
            WriteAuditLog::class.'@handleSettingUpdated',
        ],
        BaselineUpdated::class => [
            WriteAuditLog::class.'@handleBaselineUpdated',
        ],
        AccountAudited::class => [
            LogAccountAuditEntry::class,
        ],
        RecommendationAccepted::class => [
            LogRecommendationAccepted::class,
        ],
        RecommendationDismissed::class => [
            LogRecommendationDismissed::class,
        ],
        ProductionSheetImported::class => [
            LogProductionSheetImported::class,
        ],
    ];

    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
