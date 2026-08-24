<?php

use App\Livewire\Dashboard\TeamWorkloadChart;
use App\Models\Account;
use App\Models\Role;
use App\Models\Setting;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

function workloadChartUser(string $roleName, string $email): User
{
    $role = Role::firstOrCreate(['name' => $roleName], ['label' => ucfirst($roleName)]);

    return User::create([
        'first_name' => 'Test',
        'last_name' => ucfirst($roleName),
        'email' => $email,
        'password' => 'secret',
        'role_id' => $role->id,
        'is_active' => true,
    ]);
}

function workloadTask(User $assignee, User $creator, Account $account, string $reference, string $status, int $weight): Task
{
    return Task::create([
        'reference' => $reference,
        'title' => 'Sample task',
        'account_id' => $account->id,
        'assigned_to' => $assignee->id,
        'created_by' => $creator->id,
        'complexity_tier' => 'medium',
        'complexity_weight' => $weight,
        'standard_hours' => 4,
        'status' => $status,
    ]);
}

it('denies employees from viewing the team workload chart', function () {
    $employee = workloadChartUser('employee', 'employee@example.com');

    Livewire::actingAs($employee)
        ->test(TeamWorkloadChart::class)
        ->assertForbidden();
});

it('only counts in_progress tasks toward the workload score', function () {
    Setting::create(['key' => 'workload_threshold', 'value' => '12', 'type' => 'int', 'group' => 'capacity', 'label' => 'Workload threshold']);

    $manager = workloadChartUser('manager', 'manager@example.com');
    $analyst = workloadChartUser('employee', 'analyst@example.com');
    $account = Account::create(['name' => 'Account X', 'code' => 'ACC-X', 'is_active' => true]);

    workloadTask($analyst, $manager, $account, 'TSK-001', 'in_progress', 5);
    workloadTask($analyst, $manager, $account, 'TSK-002', 'in_progress', 3);
    workloadTask($analyst, $manager, $account, 'TSK-003', 'pending', 5);
    workloadTask($analyst, $manager, $account, 'TSK-004', 'completed', 5);

    $component = Livewire::actingAs($manager)->test(TeamWorkloadChart::class);
    $row = $component->get('scores')->firstWhere('user_id', $analyst->id);

    expect($row['score'])->toBe(8);
});

it('sorts scores descending with the highest workload first', function () {
    Setting::create(['key' => 'workload_threshold', 'value' => '12', 'type' => 'int', 'group' => 'capacity', 'label' => 'Workload threshold']);

    $manager = workloadChartUser('manager', 'manager2@example.com');
    $low = workloadChartUser('employee', 'low@example.com');
    $high = workloadChartUser('employee', 'high@example.com');
    $account = Account::create(['name' => 'Account Y', 'code' => 'ACC-Y', 'is_active' => true]);

    workloadTask($low, $manager, $account, 'TSK-010', 'in_progress', 1);
    workloadTask($high, $manager, $account, 'TSK-011', 'in_progress', 5);
    workloadTask($high, $manager, $account, 'TSK-012', 'in_progress', 5);

    $component = Livewire::actingAs($manager)->test(TeamWorkloadChart::class);
    $scores = $component->get('scores');

    expect($scores->first()['user_id'])->toBe($high->id);
    expect($scores->first()['score'])->toBe(10);
});
