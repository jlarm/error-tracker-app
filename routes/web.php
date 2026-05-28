<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DocsController;
use App\Http\Controllers\ErrorLogController;
use App\Http\Controllers\ProjectController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::post('projects', [ProjectController::class, 'store'])->name('projects.store');
    Route::get('projects/{project}', [ProjectController::class, 'show'])->name('projects.show');
    Route::get('errors/{errorLog}', [ErrorLogController::class, 'show'])->name('errors.show');
    Route::post('errors/{errorLog}/resolve', [ErrorLogController::class, 'resolve'])->name('errors.resolve');
    Route::post('errors/{errorLog}/unresolve', [ErrorLogController::class, 'unresolve'])->name('errors.unresolve');
    Route::get('docs', [DocsController::class, 'index'])->name('docs');
});

require __DIR__.'/settings.php';
