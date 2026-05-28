<?php

use App\Http\Controllers\Api\ErrorIngestionController;
use Illuminate\Support\Facades\Route;

Route::post('errors', [ErrorIngestionController::class, 'store'])->name('api.errors.store');
