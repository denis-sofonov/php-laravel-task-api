<?php

namespace App\Http\Controllers;

use App\Models\Task;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

/**
 * @group System
 *
 * @authenticated
 */
class StatsController extends Controller
{
    /**
     * Сводка по данным пользователя (кешируется на 60 секунд).
     *
     * Результат кешируется на 60 секунд (Cache::remember): первый запрос
     * считает агрегаты, последующие в течение TTL берут готовое из кеша.
     * Допускается небольшая неактуальность данных — обычный приём для дашбордов.
     */
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();

        $stats = Cache::remember("stats:user:{$user->id}", now()->addSeconds(60), function () use ($user) {
            return [
                'projects' => $user->projects()->count(),
                'tasks' => Task::whereHas('project', fn ($query) => $query->where('user_id', $user->id))->count(),
            ];
        });

        return response()->json(['data' => $stats]);
    }
}
