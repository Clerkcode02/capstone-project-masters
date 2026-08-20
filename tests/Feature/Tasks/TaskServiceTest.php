<?php

use App\Domain\Tasks\Enums\ComplexityTier;
use App\Domain\Tasks\Services\TaskService;
use App\Models\Account;
use App\Models\Role;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    Setting::factory()->create(['key' => 'complexity_weight_small', 'value' => '1', 'type' => 'int']);
    Setting::factory()->create(['key' => 'complexity_weight_medium', 'value' => '3', 'type' => 'int']);
    Setting::factory()->create(['key' => 'complexity_weight_large', 'value' => '5', 'type' => 'int']);

    $this->manager = User::factory()->create([
        'role_id' => Role::query()->firstOrCreate(['name' => 'manager'], ['label' => 'Manager'])->id,
    ]);

    $this->account = Account::factory()->create();
});

function taskPayload(array $overrides = []): array
{
    return array_merge([
        'title' => 'Weekly media report',
        'description' => 'Compile clippings for the week.',
        'account_id' => null,
        'standard_hours' => 4.0,
        'complexity_tier' => ComplexityTier::Medium->value,
        'due_date' => null,
    ], $overrides);
}

it('generates sequential and unique references', function () {
    $service = app(TaskService::class);

    $first = $service->create(taskPayload(['account_id' => $this->account->id]), $this->manager);
    $second = $service->create(taskPayload(['account_id' => $this->account->id]), $this->manager);
    $third = $service->create(taskPayload(['account_id' => $this->account->id]), $this->manager);

    $year = now()->year;

    expect($first->reference)->toBe("TSK-{$year}-0001");
    expect($second->reference)->toBe("TSK-{$year}-0002");
    expect($third->reference)->toBe("TSK-{$year}-0003");
    expect(collect([$first, $second, $third])->pluck('reference')->unique())->toHaveCount(3);
});

it('snapshots the complexity weight at creation time', function () {
    $service = app(TaskService::class);

    $task = $service->create(taskPayload([
        'account_id' => $this->account->id,
        'complexity_tier' => ComplexityTier::Large->value,
    ]), $this->manager);

    expect($task->complexity_weight)->toBe(5);
});

it('does not change a stored weight when the setting later changes', function () {
    $service = app(TaskService::class);

    $task = $service->create(taskPayload([
        'account_id' => $this->account->id,
        'complexity_tier' => ComplexityTier::Medium->value,
    ]), $this->manager);

    expect($task->complexity_weight)->toBe(3);

    Setting::query()->where('key', 'complexity_weight_medium')->update(['value' => '99']);

    expect($task->fresh()->complexity_weight)->toBe(3);
});
