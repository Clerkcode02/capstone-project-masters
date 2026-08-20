<?php

namespace Tests\Feature\Auth;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Volt\Volt;
use Tests\TestCase;

class AuditLoggingTest extends TestCase
{
    use RefreshDatabase;

    public function test_successful_login_writes_exactly_one_audit_row(): void
    {
        $user = User::factory()->create();

        Volt::test('pages.auth.login')
            ->set('form.email', $user->email)
            ->set('form.password', 'password')
            ->call('login');

        $this->assertAuthenticatedAs($user);

        $this->assertSame(1, AuditLog::query()->where('action', 'login')->count());

        $auditLog = AuditLog::query()->where('action', 'login')->first();

        $this->assertSame($user->id, $auditLog->user_id);
    }
}
