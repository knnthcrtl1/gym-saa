<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\BranchController;
use App\Http\Controllers\Api\CheckinController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\MemberController;
use App\Http\Controllers\Api\MembershipPlanController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\StaffController;
use App\Http\Controllers\Api\SubscriptionController;
use App\Http\Controllers\Api\TenantController;
use App\Http\Controllers\Api\WebhookController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::get('/health', fn () => response()->json([
        'ok' => true,
        'app' => config('app.name'),
        'time' => now()->toDateTimeString(),
    ]));

    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/webhooks/paymongo', [WebhookController::class, 'paymongo']);

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
        Route::apiResource('staff', StaffController::class);
        Route::get('/checkins', [CheckinController::class, 'index']);
        Route::post('/checkins', [CheckinController::class, 'store']);
        Route::get('/payments', [PaymentController::class, 'index']);
        Route::post('/payments/intent', [PaymentController::class, 'createIntent']);
        Route::post('/payments/manual', [PaymentController::class, 'storeManual']);
        Route::post('/payments/{payment}/proof', [PaymentController::class, 'uploadProof']);
        Route::put('/payments/{payment}/verify', [PaymentController::class, 'verify']);
        Route::put('/payments/{payment}/reject', [PaymentController::class, 'reject']);
        Route::get('/payments/{payment}/audit-logs', [PaymentController::class, 'auditLogs']);
        Route::get('/payments/{payment}', [PaymentController::class, 'show']);
    });
});