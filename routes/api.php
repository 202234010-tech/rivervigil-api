<?php

use App\Http\Controllers\StationController;
use Illuminate\Support\Facades\Route;

Route::get('/stations', [StationController::class, 'index']);
Route::get('/stations/{station}', [StationController::class, 'show']);

Route::prefix('v1')->group(function () {
    Route::get('/stations', [StationController::class, 'index']);
    Route::get('/stations/{station}', [StationController::class, 'show']);
});
