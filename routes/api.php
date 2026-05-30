<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\EmailVerificationController;
use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\TaskController;
use Illuminate\Support\Facades\Route;

/*
| Базовый префикс /api добавляется автоматически (bootstrap/app.php).
| Здесь добавляем версию v1 -> итоговые пути вида /api/v1/...
| Версионирование позволяет менять API, не ломая старых клиентов.
*/

Route::prefix('v1')->group(function () {
    // Публичные маршруты со строгим лимитом (защита от перебора паролей).
    Route::middleware('throttle:auth')->group(function () {
        Route::post('/register', [AuthController::class, 'register']);
        Route::post('/login', [AuthController::class, 'login']);
        Route::post('/forgot-password', [PasswordResetController::class, 'forgot']);
        Route::post('/reset-password', [PasswordResetController::class, 'reset']);
    });

    // Подтверждение email по подписанной ссылке из письма (без токена, подпись в URL).
    Route::get('/email/verify/{id}/{hash}', [EmailVerificationController::class, 'verify'])
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');

    // Защищённые маршруты: валидный токен + общий лимит частоты.
    Route::middleware(['auth:sanctum', 'throttle:api'])->group(function () {
        Route::get('/user', [AuthController::class, 'me']);
        Route::post('/logout', [AuthController::class, 'logout']);

        // Повторная отправка письма подтверждения.
        Route::post('/email/verification-notification', [EmailVerificationController::class, 'resend'])
            ->name('verification.send');

        Route::apiResource('projects', ProjectController::class);
        Route::apiResource('projects.tasks', TaskController::class)->shallow();
    });
});
