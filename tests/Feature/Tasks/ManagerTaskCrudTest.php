<?php

use App\Models\Account;
use App\Models\Role;
use App\Models\Setting;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    Setting::factory()->create(['key' => 'complexity_weight_small', 'value' => '1', 'type' => 'int']);
    Setting::factory()->create(['key' => 'complexity_weight_medium', 'value' => '3', 'type' => 'int']);
    Setting::factory()->create(['key' => 'complexity_weight_large', 'value' => '5', 'type' => 'int']);
});

it('lets a manager create, update, and delete a task', function () {
    $manager = User::factory()->create([
        'role_id' => Role::query()->firstOrCreate(['name' => 'manager'], ['label' => 'Manager'])->id,
    ]);
    $account = Account::factory()->create();

    $store = $this->actingAs($manager)->post(route('manager.tasks.store'), [
        'title' => 'Compile weekly clippings',
        'description' => 'For Account X',
        'account_id' => $account->id,
        'standard_hours' => 4,
        'complexity_tier' => 'medium',
        'due_date' => now()->addWeek()->format('Y-m-d'),
    ]);

    $store->assertRedirect(route('manager.tasks.index'));

    $task = Task::query()->firstOrFail();
    expect($task->reference)->toStartWith('TSK-'.now()->year.'-');
    expect($task->complexity_weight)->toBe(3);
    expect($task->status->value)->toBe('pending');
    expect($task->created_by)->toBe($manager->id);

    $update = $this->actingAs($manager)->put(route('manager.tasks.update', $task), [
        'title' => 'Compile weekly clippings (revised)',
        'description' => $task->description,
        'account_id' => $account->id,
        'standard_hours' => 6,
        'complexity_tier' => 'large',
        'due_date' => now()->addWeeks(2)->format('Y-m-d'),
    ]);

    $update->assertRedirect(route('manager.tasks.index'));
    $task->refresh();
    expect($task->title)->toBe('Compile weekly clippings (revised)');
    expect($task->complexity_weight)->toBe(5);

    $destroy = $this->actingAs($manager)->delete(route('manager.tasks.destroy', $task));
    $destroy->assertRedirect(route('manager.tasks.index'));
    expect(Task::query()->find($task->id))->toBeNull();
    expect(Task::withTrashed()->find($task->id))->not->toBeNull();
});

it('forbids an employee from creating a task', function () {
    $employee = User::factory()->create([
        'role_id' => Role::query()->firstOrCreate(['name' => 'employee'], ['label' => 'Employee'])->id,
    ]);
    $account = Account::factory()->create();

    $this->actingAs($employee)->post(route('manager.tasks.store'), [
        'title' => 'Should not work',
        'account_id' => $account->id,
        'standard_hours' => 2,
        'complexity_tier' => 'small',
    ])->assertForbidden();
});
