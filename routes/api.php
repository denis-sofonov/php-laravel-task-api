<?php

use App\Http\Controllers\Auth\AuthController;
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
    });

    // Защищённые маршруты: валидный токен + общий лимит частоты.
    Route::middleware(['auth:sanctum', 'throttle:api'])->group(function () {
        Route::get('/user', [AuthController::class, 'me']);
        Route::post('/logout', [AuthController::class, 'logout']);

        Route::apiResource('projects', ProjectController::class);
        Route::apiResource('projects.tasks', TaskController::class)->shallow();
    });
});
