<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\EmailVerificationController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\HealthController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\StatsController;
use App\Http\Controllers\TaskController;
use Illuminate\Support\Facades\Route;

/*
| The /api prefix is added automatically (bootstrap/app.php). Here we add the v1
| version -> final paths like /api/v1/... Versioning lets the API evolve without
| breaking existing clients.
*/

Route::prefix('v1')->group(function () {
    // Health check for load balancers / k8s — public, unthrottled.
    Route::get('/health', [HealthController::class, 'show']);

    // Public routes with a strict limit (password brute-force protection).
    Route::middleware('throttle:auth')->group(function () {
        Route::post('/register', [AuthController::class, 'register']);
        Route::post('/login', [AuthController::class, 'login']);
        Route::post('/forgot-password', [PasswordResetController::class, 'forgot']);
        Route::post('/reset-password', [PasswordResetController::class, 'reset']);
    });

    // Email verification via a signed link (no token; the URL signature authenticates).
    Route::get('/email/verify/{id}/{hash}', [EmailVerificationController::class, 'verify'])
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');

    // Protected routes: valid token + general rate limit.
    Route::middleware(['auth:sanctum', 'throttle:api'])->group(function () {
        Route::get('/user', [AuthController::class, 'me']);
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/stats', [StatsController::class, 'show']);

        Route::post('/email/verification-notification', [EmailVerificationController::class, 'resend'])
            ->name('verification.send');

        Route::apiResource('projects', ProjectController::class);
        Route::apiResource('projects.tasks', TaskController::class)->shallow();
    });
});
