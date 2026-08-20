---
name: laravel-conventions
description: Code structure, security, validation, authorization, and audit conventions for this Laravel modular monolith. Use when creating or editing controllers, Livewire components, services, form requests, policies, middleware, events, listeners, commands, or routes, and when reviewing code for security or architectural compliance.
---

# Laravel Conventions

## Where code goes

| Concern | Location |
|---|---|
| Business logic | `app/Domain/<Module>/Services/*Service.php` |
| Value objects | `app/Domain/<Module>/DTOs/` |
| Enums | `app/Domain/<Module>/Enums/` |
| HTTP entry points | `app/Http/Controllers/{Admin,Manager,Employee}/` |
| Interactive UI | `app/Livewire/<Area>/` |
| Validation | `app/Http/Requests/` |
| Authorization | `app/Policies/` |
| Models | `app/Models/` (flat) |
| Audit side-effects | `app/Events/` + `app/Listeners/` |

**Controllers and Livewire components are thin.** Validate → call a service →
return. If a method exceeds ~15 lines or contains a formula, extract a service.
Services never touch `request()`, `session()`, or `auth()` — pass what they need
as arguments so they stay unit-testable.

## Modules

```
Identity  Accounts  Tasks  TimeTracking  Capacity  Optimization  Reporting  Administration
```

Cross-module calls go through services, never by reaching into another module's
internals. A module may query another module's Eloquent models read-only.

## Write path — always all five layers

```
Route (+ middleware) → FormRequest → Policy → Service → Model → Event → Listener
```

Skipping the FormRequest "because it's simple" is not allowed. Skipping the
Policy is a security defect.

## Enums

PHP backed enums, string-backed, in the owning module:

```php
enum ComplexityTier: string {
    case Small = 'small';
    case Medium = 'medium';
    case Large = 'large';

    public function label(): string { ... }
    public function badgeClasses(): string { ... }
}
```

Cast on the model. Validate with `Rule::enum(ComplexityTier::class)`.
Never compare against raw strings scattered through Blade.

## Authorization — three layers, all required

**1. Route middleware**
```php
Route::middleware(['auth','role:administrator'])->prefix('admin')->group(...);
Route::middleware(['auth','role:manager,administrator'])->prefix('manager')->group(...);
Route::middleware(['auth'])->prefix('my')->group(...);
```

**2. Policies** — registered in `AuthServiceProvider`, called via
`$this->authorize()` or `@can`. Livewire components authorize in `mount()`
**and** again in any action method (a mounted component's actions are separately
reachable).

**3. Query scopes** — `->visibleTo(auth()->user())` on every list query.

Do not install `spatie/laravel-permission`. Three fixed roles need a `role_id`
column, a `role` middleware, and Policies.

## Validation rules that matter

```php
'complexity_tier'  => ['required', Rule::enum(ComplexityTier::class)],
'standard_hours'   => ['required','numeric','min:0.25','max:999.99'],
'duration_minutes' => ['required','integer','min:1','max:1440'],
'log_date'         => ['required','date','before_or_equal:today'],
'utilization_target'=> ['required','numeric','min:0','max:1'],
'baseline_hours'   => ['required','numeric','min:1','max:744'],
'sheet'            => ['required','file','mimes:csv,txt,xlsx','max:5120'],
```

Never trust a hidden form field for `user_id` — always derive the actor from
`auth()->id()`.

## Security checklist for every PR

- [ ] `@csrf` on every form (Livewire handles its own)
- [ ] `{{ }}` escaping — `{!! !!}` is banned
- [ ] Explicit `$fillable` on touched models
- [ ] No string interpolation in queries; bindings only, including in `selectRaw`
- [ ] Policy check present and tested
- [ ] Query scoped to the actor
- [ ] Uploads: mime + size validated, random filename, stored in
      `storage/app/imports` (outside webroot), never `public/`
- [ ] Audit event fired for any state change a panelist would ask about
- [ ] No secret, key, or password in code or logs

## Audit logging

Fire an event; a listener writes `audit_logs`. Do not call the audit service
inline from controllers.

```php
TaskAssigned::dispatch($task, $assignee, auth()->user(), $wasOverAllocationOverride);
```

## Livewire

- Real-time feel = `wire:poll.30s` on dashboard components. No websockets, no Reverb.
- Public properties are user-modifiable — never store a computed permission or
  price in one. Re-derive on the server in the action method.
- Use `#[Computed]` for derived values instead of recalculating in Blade.
- Keep components under ~150 lines; extract to a service.

## Commands

```php
php artisan capacity:recalculate {--year=} {--month=} {--user=}
php artisan optimization:detect
```

Both are idempotent and safe to re-run. Both print a summary table. Registered
in `routes/console.php`: capacity at 01:00 daily, detection at 01:15 daily.

## Queues and jobs

`database` driver. Only two things are queued: production-sheet import and PDF
generation. Everything else is synchronous. Do not queue capacity recalculation
— the demo needs it to complete while the examiner watches.

## Naming

- Services: `CapacityCalculationService`, `WorkloadScoreService` (noun + `Service`)
- Requests: `StoreTaskRequest`, `AssignTaskRequest`
- Policies: `TaskPolicy`
- Events: past tense — `TaskAssigned`, `BottleneckDetected`
- Blade components: `<x-tier-badge>`, `<x-workload-bar>`, `<x-stat-card>`
- Migrations: default Laravel timestamps, one table per file

## Style

Run `./vendor/bin/pint` before finishing any task. Use typed properties, typed
parameters, and typed return values everywhere. Constructor property promotion
for service dependencies.

## Forbidden

`spatie/laravel-permission` · Sanctum/Passport · `routes/api.php` endpoints ·
Redis · websockets · Docker · any ML or AI package · any external HTTP API call ·
`$guarded = []` · raw SQL string interpolation · business logic in Blade.
