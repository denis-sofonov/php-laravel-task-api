<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\TaskController;
use Illuminate\Support\Facades\Route;

/*
| Все эти маршруты автоматически получают префикс /api (см. bootstrap/app.php).
*/

// Публичные маршруты — доступны без токена.
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Защищённые маршруты — требуют валидный Bearer-токен Sanctum.
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // CRUD проектов: index/store/show/update/destroy.
    Route::apiResource('projects', ProjectController::class);

    // Задачи вложены в проект. shallow(): index/store через /projects/{project}/tasks,
    // а show/update/destroy через короткий /tasks/{task}.
    Route::apiResource('projects.tasks', TaskController::class)->shallow();
});
