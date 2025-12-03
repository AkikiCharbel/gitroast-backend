<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Redis;

class HealthController extends Controller
{
    /**
     * Health check endpoint.
     */
    public function index(): JsonResponse
    {
        return response()->json([
            'status' => 'healthy',
            'timestamp' => now()->toIso8601String(),
            'services' => [
                'database' => $this->checkDatabase(),
                'redis' => $this->checkRedis(),
                'queue' => $this->checkQueue(),
            ],
        ]);
    }

    private function checkDatabase(): string
    {
        try {
            DB::connection()->getPdo();

            return 'connected';
        } catch (\Exception) {
            return 'disconnected';
        }
    }

    private function checkRedis(): string
    {
        try {
            Redis::ping();

            return 'connected';
        } catch (\Exception) {
            return 'disconnected';
        }
    }

    private function checkQueue(): string
    {
        try {
            $size = Queue::size();

            return "ready (pending: {$size})";
        } catch (\Exception) {
            return 'unavailable';
        }
    }
}
