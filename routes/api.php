<?php

use App\Http\Controllers\Api\ErrorIngestionController;
use App\Http\Controllers\Api\V1\ErrorLogController;
use Illuminate\Support\Facades\Route;

Route::post('errors', [ErrorIngestionController::class, 'store'])->name('api.errors.store');

Route::middleware('auth:sanctum')->prefix('v1')->name('api.v1.')->group(function () {
    Route::get('errors', [ErrorLogController::class, 'index'])->name('errors.index');
    Route::post('errors/{errorLog}/resolve', [ErrorLogController::class, 'resolve'])->name('errors.resolve');
    Route::post('errors/{errorLog}/unresolve', [ErrorLogController::class, 'unresolve'])->name('errors.unresolve');
});
