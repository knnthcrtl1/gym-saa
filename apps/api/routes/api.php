<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BranchController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\MemberController;
use App\Http\Controllers\Api\MembershipPlanController;
use App\Http\Controllers\Api\SubscriptionController;
use App\Http\Controllers\Api\TenantController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::get('/health', fn () => response()->json([
        'ok' => true,
        'app' => config('app.name'),
        'time' => now()->toDateTimeString(),
    ]));

    Route::post('/login', [AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);
        Route::get('/dashboard', [DashboardController::class, 'index']);

        Route::apiResource('tenants', TenantController::class);
        Route::apiResource('branches', BranchController::class);
        Route::delete('/members/bulk-delete', [MemberController::class, 'bulkDestroy']);
        Route::apiResource('members', MemberController::class);
        Route::apiResource('membership-plans', MembershipPlanController::class);
        Route::apiResource('subscriptions', SubscriptionController::class);
    });
});