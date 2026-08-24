<?php

use App\Http\Controllers\Admin\AccountController as AdminAccountController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Employee\AccountController as EmployeeAccountController;
use App\Http\Controllers\Employee\DashboardController as EmployeeDashboardController;
use App\Http\Controllers\Manager\AccountController as ManagerAccountController;
use App\Http\Controllers\Manager\DashboardController as ManagerDashboardController;
use App\Http\Controllers\RoleRedirectController;
use App\Http\Controllers\TimeTracking\ProductionSheetTemplateController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome');

Route::get('dashboard', RoleRedirectController::class)
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware(['auth', 'verified', 'role:administrator'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {
        Route::get('/dashboard', AdminDashboardController::class)->name('dashboard');

        Route::resource('accounts', AdminAccountController::class)->except(['show']);
        Route::post('accounts/{account}/users', [AdminAccountController::class, 'assignUser'])->name('accounts.users.assign');
        Route::delete('accounts/{account}/users/{user}', [AdminAccountController::class, 'unassignUser'])->name('accounts.users.unassign');
    });

Route::middleware(['auth', 'verified', 'role:manager,administrator'])
    ->prefix('manager')
    ->name('manager.')
    ->group(function () {
        Route::get('/dashboard', ManagerDashboardController::class)->name('dashboard');

        Route::get('accounts', [ManagerAccountController::class, 'index'])->name('accounts.index');
        Route::get('accounts/{account}', [ManagerAccountController::class, 'show'])->name('accounts.show');
    });

Route::middleware(['auth', 'verified'])
    ->prefix('my')
    ->name('my.')
    ->group(function () {
        Route::get('/dashboard', EmployeeDashboardController::class)->name('dashboard');

        Route::get('accounts', [EmployeeAccountController::class, 'index'])->name('accounts.index');
        Route::get('accounts/{account}', [EmployeeAccountController::class, 'show'])->name('accounts.show');
    });

Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

Route::middleware(['auth'])->prefix('time-tracking')->name('time-tracking.')->group(function () {
    Route::view('/', 'time-tracking.index')->name('index');
    Route::get('/import/template', ProductionSheetTemplateController::class)->name('import.template');
});

Route::middleware(['auth', 'role:manager,administrator'])->prefix('optimization')->name('optimization.')->group(function () {
    Route::view('/recommendations', 'optimization.recommendations')->name('recommendations');
});

Route::middleware(['auth', 'role:manager,administrator'])->prefix('dashboard')->name('dashboard.')->group(function () {
    Route::view('/monthly-at-a-glance', 'monthly-at-a-glance')->name('monthly');
    Route::view('/quarterly-at-a-glance', 'quarterly-at-a-glance')->name('quarterly');
    Route::view('/team-workload', 'team-workload')->name('workload');
});

require __DIR__.'/auth.php';
