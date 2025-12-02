<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreateCheckoutRequest;
use App\Models\Analysis;
use App\Services\Payment\PaymentService;
use Illuminate\Http\JsonResponse;

class CheckoutController extends Controller
{
    public function __construct(
        private readonly PaymentService $paymentService
    ) {}

    /**
     * Create a checkout session.
     */
    public function create(CreateCheckoutRequest $request): JsonResponse
    {
        $analysis = Analysis::where('uuid', $request->validated('analysis_id'))->firstOrFail();

        if ($analysis->is_paid) {
            return response()->json([
                'message' => 'Analysis is already paid.',
                'data' => [
                    'analysis_id' => $analysis->uuid,
                ],
            ], 400);
        }

        if (! $analysis->isComplete()) {
            return response()->json([
                'message' => 'Analysis must be completed before checkout.',
            ], 400);
        }

        try {
            $session = $this->paymentService->createCheckoutSession($analysis);

            return response()->json([
                'data' => [
                    'session_id' => $session->sessionId,
                    'checkout_url' => $session->checkoutUrl,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to create checkout session.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Verify a payment.
     */
    public function verify(string $sessionId): JsonResponse
    {
        $isPaid = $this->paymentService->verifyPayment($sessionId);

        return response()->json([
            'data' => [
                'is_paid' => $isPaid,
            ],
        ]);
    }
}
