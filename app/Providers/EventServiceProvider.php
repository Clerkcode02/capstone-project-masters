<?php

namespace App\Providers;

use App\Events\BaselineUpdated;
use App\Events\RoleChanged;
use App\Events\SettingUpdated;
use App\Events\UserCreated;
use App\Events\UserUpdated;
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
    ];

    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
