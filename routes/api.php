<?php

use App\Http\Controllers\BookController;
use App\Http\Controllers\StationController;
use Illuminate\Support\Facades\Route;

Route::apiResource('books', BookController::class);
Route::apiResource('stations', StationController::class);

Route::prefix('v1')->group(function () {
    Route::apiResource('books', BookController::class);
    Route::apiResource('stations', StationController::class);
});
