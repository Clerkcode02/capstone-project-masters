<?php

use App\Http\Controllers\TimeTracking\ProductionSheetTemplateController;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

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
});

require __DIR__.'/auth.php';
