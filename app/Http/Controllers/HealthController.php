<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Throwable;

class HealthController extends Controller
{
    /**
     * Проверка живости сервиса для load balancer / k8s probes.
     * Возвращает 200, если БД доступна, иначе 503.
     */
    public function show(): JsonResponse
    {
        $databaseOk = true;

        try {
            DB::connection()->select('select 1');
        } catch (Throwable) {
            $databaseOk = false;
        }

        return response()->json([
            'status' => $databaseOk ? 'ok' : 'degraded',
            'services' => [
                'database' => $databaseOk ? 'ok' : 'down',
            ],
            'timestamp' => now()->toIso8601String(),
        ], $databaseOk ? 200 : 503);
    }
}
