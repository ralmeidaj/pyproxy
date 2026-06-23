<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

class HealthController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $checks = [
            'database' => $this->checkDatabase(),
            'redis'    => $this->checkRedis(),
            'storage'  => $this->checkStorage(),
        ];

        $allOk = collect($checks)->every(fn ($s) => $s === 'ok');

        return response()->json([
            'status'    => $allOk ? 'ok' : 'degraded',
            'timestamp' => now()->toIso8601String(),
            'checks'    => $checks,
        ], $allOk ? 200 : 503);
    }

    private function checkDatabase(): string
    {
        try {
            DB::selectOne('SELECT 1');
            return 'ok';
        } catch (Throwable) {
            return 'fail';
        }
    }

    private function checkRedis(): string
    {
        try {
            Cache::store('redis')->put('health_check', 1, 5);
            return 'ok';
        } catch (Throwable) {
            return 'fail';
        }
    }

    private function checkStorage(): string
    {
        try {
            Storage::put('.health', '1');
            Storage::delete('.health');
            return 'ok';
        } catch (Throwable) {
            return 'fail';
        }
    }
}
