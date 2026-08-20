<?php

use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Employee\DashboardController as EmployeeDashboardController;
use App\Http\Controllers\Manager\DashboardController as ManagerDashboardController;
use App\Http\Controllers\RoleRedirectController;
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
    });

Route::middleware(['auth', 'verified', 'role:manager,administrator'])
    ->prefix('manager')
    ->name('manager.')
    ->group(function () {
        Route::get('/dashboard', ManagerDashboardController::class)->name('dashboard');

        Route::view('/production-sheet-import', 'manager.production-sheet-import')
            ->name('production-sheet-import');

        Route::get('/production-sheet-template', fn () => response()->download(
            base_path('docs/samples/production-sheet-template.csv'),
            'production-sheet-template.csv'
        ))->name('production-sheet-template');
    });

Route::middleware(['auth', 'verified'])
    ->prefix('my')
    ->name('my.')
    ->group(function () {
        Route::get('/dashboard', EmployeeDashboardController::class)->name('dashboard');

        Route::view('/time-tracking', 'employee.time-tracking')->name('time-tracking');
    });

Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

require __DIR__.'/auth.php';
