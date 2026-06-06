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
     * Per-user counts.
     *
     * Cached for 60s: the aggregates are slightly stale within the TTL, which is
     * an acceptable trade-off for dashboard-style numbers.
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
