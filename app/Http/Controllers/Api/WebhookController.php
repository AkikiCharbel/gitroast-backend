<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Payment\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    public function __construct(
        private readonly PaymentService $paymentService
    ) {}

    /**
     * Handle Paddle webhook.
     */
    public function paddle(Request $request): JsonResponse
    {
        $payload = $request->getContent();
        $signature = $request->header('Paddle-Signature', '');

        try {
            $this->paymentService->handleWebhook($payload, $signature);

            return response()->json(['status' => 'success']);
        } catch (\Exception $e) {
            Log::error('Paddle webhook failed', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 400);
        }
    }
}
