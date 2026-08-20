<?php

namespace App\Listeners;

use App\Domain\Administration\Services\AuditLogService;
use App\Events\BaselineUpdated;
use App\Events\RoleChanged;
use App\Events\SettingUpdated;
use App\Events\UserCreated;
use App\Events\UserUpdated;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Http\Request;

class WriteAuditLog
{
    public function __construct(
        private readonly AuditLogService $auditLogService,
        private readonly Request $request,
    ) {}

    public function handleLogin(Login $event): void
    {
        $this->auditLogService->log(
            userId: $event->user->id,
            action: 'login',
            auditableType: $event->user::class,
            auditableId: $event->user->id,
            ipAddress: $this->ipAddress(),
            userAgent: $this->userAgent(),
        );
    }

    public function handleLogout(Logout $event): void
    {
        $this->auditLogService->log(
            userId: $event->user?->id,
            action: 'logout',
            auditableType: $event->user ? $event->user::class : null,
            auditableId: $event->user?->id,
            ipAddress: $this->ipAddress(),
            userAgent: $this->userAgent(),
        );
    }

    public function handleFailedLogin(Failed $event): void
    {
        $email = $event->credentials['email'] ?? null;

        $this->auditLogService->log(
            userId: $event->user?->id,
            action: 'failed_login',
            description: $email ? "Failed login attempt for {$email}" : 'Failed login attempt',
            ipAddress: $this->ipAddress(),
            userAgent: $this->userAgent(),
        );
    }

    public function handleUserCreated(UserCreated $event): void
    {
        $this->auditLogService->log(
            userId: $event->actor?->id,
            action: 'user_created',
            auditableType: $event->user::class,
            auditableId: $event->user->id,
            description: "Created user {$event->user->email}",
            ipAddress: $this->ipAddress(),
            userAgent: $this->userAgent(),
        );
    }

    public function handleUserUpdated(UserUpdated $event): void
    {
        $this->auditLogService->log(
            userId: $event->actor?->id,
            action: 'user_updated',
            auditableType: $event->user::class,
            auditableId: $event->user->id,
            description: "Updated user {$event->user->email}",
            ipAddress: $this->ipAddress(),
            userAgent: $this->userAgent(),
        );
    }

    public function handleRoleChanged(RoleChanged $event): void
    {
        $this->auditLogService->log(
            userId: $event->actor?->id,
            action: 'role_changed',
            auditableType: $event->user::class,
            auditableId: $event->user->id,
            description: "Changed role for {$event->user->email} from {$event->fromRole} to {$event->toRole}",
            ipAddress: $this->ipAddress(),
            userAgent: $this->userAgent(),
        );
    }

    public function handleSettingUpdated(SettingUpdated $event): void
    {
        $this->auditLogService->log(
            userId: $event->actor?->id,
            action: 'setting_updated',
            auditableType: $event->setting::class,
            auditableId: $event->setting->id,
            description: "Changed setting {$event->setting->key} from {$event->oldValue} to {$event->newValue}",
            ipAddress: $this->ipAddress(),
            userAgent: $this->userAgent(),
        );
    }

    public function handleBaselineUpdated(BaselineUpdated $event): void
    {
        $this->auditLogService->log(
            userId: $event->actor?->id,
            action: 'baseline_updated',
            auditableType: $event->baseline::class,
            auditableId: $event->baseline->id,
            description: "Updated baseline for {$event->baseline->period_year}-{$event->baseline->period_month}",
            ipAddress: $this->ipAddress(),
            userAgent: $this->userAgent(),
        );
    }

    private function ipAddress(): ?string
    {
        return $this->request->ip();
    }

    private function userAgent(): ?string
    {
        $userAgent = $this->request->userAgent();

        return $userAgent === null ? null : substr($userAgent, 0, 255);
    }
}
