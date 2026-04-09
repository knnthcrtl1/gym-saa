<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\MemberController;

Route::prefix('v1')->group(function () {
    Route::get('/health', fn () => response()->json([
        'ok' => true,
        'app' => config('app.name'),
        'time' => now()->toDateTimeString(),
    ]));

    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/me', [AuthController::class, 'me']);
        Route::get('/dashboard', [DashboardController::class, 'index']);
        Route::apiResource('members', MemberController::class);
    });
});