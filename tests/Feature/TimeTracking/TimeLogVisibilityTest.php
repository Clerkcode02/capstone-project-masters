<?php

namespace Tests\Feature\TimeTracking;

use App\Domain\TimeTracking\Enums\EntryMethod;
use App\Domain\TimeTracking\Enums\HourType;
use App\Models\Role;
use App\Models\TimeLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TimeLogVisibilityTest extends TestCase
{
    use RefreshDatabase;

    private function userWithRole(string $roleName): User
    {
        $role = Role::query()->firstOrCreate(
            ['name' => $roleName],
            ['label' => ucfirst($roleName)]
        );

        return User::factory()->create(['role_id' => $role->id]);
    }

    public function test_an_employee_cannot_view_another_employees_time_logs(): void
    {
        $owner = $this->userWithRole('employee');
        $other = $this->userWithRole('employee');

        $timeLog = TimeLog::create([
            'user_id' => $owner->id,
            'log_date' => now()->toDateString(),
            'hour_type' => HourType::Production->value,
            'duration_minutes' => 60,
            'entry_method' => EntryMethod::Manual->value,
        ]);

        $this->assertTrue($owner->can('view', $timeLog));
        $this->assertFalse($other->can('view', $timeLog));
    }

    public function test_the_visible_to_scope_excludes_other_employees_time_logs(): void
    {
        $owner = $this->userWithRole('employee');
        $other = $this->userWithRole('employee');

        TimeLog::create([
            'user_id' => $owner->id,
            'log_date' => now()->toDateString(),
            'hour_type' => HourType::Production->value,
            'duration_minutes' => 60,
            'entry_method' => EntryMethod::Manual->value,
        ]);

        $visibleToOther = TimeLog::query()->visibleTo($other)->count();
        $visibleToOwner = TimeLog::query()->visibleTo($owner)->count();

        $this->assertSame(0, $visibleToOther);
        $this->assertSame(1, $visibleToOwner);
    }

    public function test_a_manager_can_view_any_employees_time_log(): void
    {
        $employee = $this->userWithRole('employee');
        $manager = $this->userWithRole('manager');

        $timeLog = TimeLog::create([
            'user_id' => $employee->id,
            'log_date' => now()->toDateString(),
            'hour_type' => HourType::Production->value,
            'duration_minutes' => 60,
            'entry_method' => EntryMethod::Manual->value,
        ]);

        $this->assertTrue($manager->can('view', $timeLog));
    }
}
