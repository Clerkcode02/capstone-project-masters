<?php

use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Employee\DashboardController as EmployeeDashboardController;
use App\Http\Controllers\Employee\TaskController as EmployeeTaskController;
use App\Http\Controllers\Manager\DashboardController as ManagerDashboardController;
use App\Http\Controllers\Manager\TaskController as ManagerTaskController;
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
        Route::resource('tasks', ManagerTaskController::class)->except(['show']);
    });

Route::middleware(['auth', 'verified'])
    ->prefix('my')
    ->name('my.')
    ->group(function () {
        Route::get('/dashboard', EmployeeDashboardController::class)->name('dashboard');
        Route::get('/tasks', [EmployeeTaskController::class, 'index'])->name('tasks.index');
        Route::patch('/tasks/{task}/start', [EmployeeTaskController::class, 'start'])->name('tasks.start');
        Route::patch('/tasks/{task}/complete', [EmployeeTaskController::class, 'complete'])->name('tasks.complete');
    });

Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

require __DIR__.'/auth.php';
