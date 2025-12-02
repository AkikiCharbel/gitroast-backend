<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Actions\Analysis\CreateAnalysisAction;
use App\DTOs\Analysis\CreateAnalysisDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\AnalyzeRequest;
use App\Http\Resources\AnalysisResource;
use App\Http\Resources\AnalysisStatusResource;
use App\Http\Resources\FullAnalysisResource;
use App\Models\Analysis;
use App\Models\AnalysisRequest;
use Illuminate\Http\JsonResponse;

class AnalysisController extends Controller
{
    private const int RATE_LIMIT_PER_HOUR = 10;

    /**
     * Create a new analysis.
     */
    public function store(AnalyzeRequest $request, CreateAnalysisAction $action): JsonResponse
    {
        $ipAddress = $request->ip() ?? 'unknown';

        $requestCount = AnalysisRequest::getCountForIp($ipAddress, 1);
        if ($requestCount >= self::RATE_LIMIT_PER_HOUR) {
            return response()->json([
                'message' => 'Rate limit exceeded. Please try again later.',
                'retry_after' => 3600,
            ], 429);
        }

        AnalysisRequest::incrementForIp($ipAddress);

        $dto = new CreateAnalysisDTO(
            username: $request->validated('username'),
            ipAddress: $ipAddress,
        );

        $analysis = $action->execute($dto);

        return response()->json([
            'data' => [
                'id' => $analysis->uuid,
                'username' => $analysis->github_username,
                'status' => $analysis->status->value,
                'created_at' => $analysis->created_at?->toIso8601String(),
            ],
            'links' => [
                'self' => url("/api/analysis/{$analysis->uuid}"),
                'status' => url("/api/analysis/{$analysis->uuid}/status"),
            ],
        ], 202);
    }

    /**
     * Get analysis result.
     */
    public function show(string $uuid): AnalysisResource|JsonResponse
    {
        $analysis = Analysis::where('uuid', $uuid)->first();

        if (! $analysis) {
            return response()->json([
                'message' => 'Analysis not found.',
            ], 404);
        }

        return new AnalysisResource($analysis);
    }

    /**
     * Get analysis status.
     */
    public function status(string $uuid): AnalysisStatusResource|JsonResponse
    {
        $analysis = Analysis::where('uuid', $uuid)->first();

        if (! $analysis) {
            return response()->json([
                'message' => 'Analysis not found.',
            ], 404);
        }

        return new AnalysisStatusResource($analysis);
    }

    /**
     * Get full analysis (paid only).
     */
    public function full(string $uuid): FullAnalysisResource|JsonResponse
    {
        $analysis = Analysis::where('uuid', $uuid)->first();

        if (! $analysis) {
            return response()->json([
                'message' => 'Analysis not found.',
            ], 404);
        }

        if (! $analysis->is_paid) {
            return response()->json([
                'message' => 'Payment required for full report',
                'links' => [
                    'checkout' => url('/api/checkout/create'),
                ],
            ], 402);
        }

        return new FullAnalysisResource($analysis);
    }
}
