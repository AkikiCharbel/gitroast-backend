<?php

declare(strict_types=1);

namespace App\Services\Payment;

use App\DTOs\Payment\CheckoutSessionDTO;
use App\Exceptions\PaymentException;
use App\Models\Analysis;
use App\Models\Payment;
use Paddle\SDK\Client as PaddleClient;
use Paddle\SDK\Entities\Shared\CustomData;
use Paddle\SDK\Entities\Transaction;
use Paddle\SDK\Environment;
use Paddle\SDK\Exceptions\ApiError;
use Paddle\SDK\Exceptions\SdkException;
use Paddle\SDK\Notifications\PaddleSignature;
use Paddle\SDK\Notifications\Secret;
use Paddle\SDK\Options;
use Paddle\SDK\Resources\Transactions\Operations\Create\TransactionCreateItem;
use Paddle\SDK\Resources\Transactions\Operations\CreateTransaction;

class PaymentService
{
    private PaddleClient $client;

    public function __construct()
    {
        $environment = config('services.paddle.sandbox', false)
            ? Environment::SANDBOX
            : Environment::PRODUCTION;

        $this->client = new PaddleClient(
            apiKey: config('services.paddle.api_key', ''),
            options: new Options($environment),
        );
    }

    public function createCheckoutSession(Analysis $analysis): CheckoutSessionDTO
    {
        if ($analysis->is_paid) {
            throw new PaymentException('Analysis is already paid');
        }

        try {
            $transaction = $this->client->transactions->create(
                new CreateTransaction(
                    items: [
                        new TransactionCreateItem(
                            priceId: (string) config('services.paddle.price_full_report'),
                            quantity: 1,
                        ),
                    ],
                    customData: new CustomData([
                        'analysis_id' => (string) $analysis->id,
                        'analysis_uuid' => $analysis->uuid,
                        'github_username' => $analysis->github_username,
                    ]),
                )
            );

            // Create pending payment record
            Payment::create([
                'analysis_id' => $analysis->id,
                'paddle_transaction_id' => $transaction->id,
                'amount_cents' => 0, // Will be updated on webhook
                'currency' => 'USD',
                'status' => 'pending',
            ]);

            // Build checkout URL
            $checkoutUrl = $this->buildCheckoutUrl($transaction, $analysis);

            return new CheckoutSessionDTO(
                sessionId: $transaction->id,
                checkoutUrl: $checkoutUrl,
            );
        } catch (ApiError|SdkException $e) {
            throw new PaymentException(
                "Failed to create checkout session: {$e->getMessage()}",
                previous: $e
            );
        }
    }

    private function buildCheckoutUrl(Transaction $transaction, Analysis $analysis): string
    {
        $baseUrl = config('services.paddle.sandbox', false)
            ? 'https://sandbox-checkout.paddle.com/checkout/custom'
            : 'https://checkout.paddle.com/checkout/custom';

        $successUrl = config('app.frontend_url').'/success?transaction_id='.$transaction->id;
        $cancelUrl = config('app.frontend_url')."/analyze/{$analysis->uuid}";

        return $baseUrl.'?'.http_build_query([
            'transaction_id' => $transaction->id,
            'success_url' => $successUrl,
            'cancel_url' => $cancelUrl,
        ]);
    }

    public function handleWebhook(string $payload, string $signature): void
    {
        // Verify webhook signature
        $webhookSecret = config('services.paddle.webhook_secret', '');

        if ($webhookSecret !== '') {
            try {
                $paddleSignature = PaddleSignature::parse($signature);
                $secret = new Secret($webhookSecret);

                if (! $paddleSignature->verify($payload, $secret)) {
                    throw new PaymentException('Webhook signature verification failed');
                }
            } catch (\Exception $e) {
                throw new PaymentException("Webhook verification error: {$e->getMessage()}");
            }
        }

        /** @var array<string, mixed> $data */
        $data = json_decode($payload, true);

        $eventType = $data['event_type'] ?? '';

        match ($eventType) {
            'transaction.completed' => $this->handleTransactionCompleted($data),
            'transaction.payment_failed' => $this->handleTransactionFailed($data),
            default => null,
        };
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function handleTransactionCompleted(array $data): void
    {
        $transactionData = $data['data'] ?? [];
        $transactionId = $transactionData['id'] ?? '';
        $customData = $transactionData['custom_data'] ?? [];
        $details = $transactionData['details'] ?? [];
        $totals = $details['totals'] ?? [];

        $payment = Payment::where('paddle_transaction_id', $transactionId)->first();

        if (! $payment) {
            // Try to find by analysis_id from custom_data
            $analysisId = $customData['analysis_id'] ?? null;
            if ($analysisId) {
                $payment = Payment::where('analysis_id', (int) $analysisId)
                    ->where('status', 'pending')
                    ->first();
            }
        }

        if (! $payment) {
            return;
        }

        // Get customer email from billing details
        $billingDetails = $transactionData['billing_details'] ?? [];
        $customerEmail = $billingDetails['email'] ?? null;

        // Get amount in cents
        $amountCents = (int) ($totals['grand_total'] ?? 0);
        $currency = strtoupper((string) ($transactionData['currency_code'] ?? 'USD'));

        $payment->update([
            'status' => 'completed',
            'paddle_transaction_id' => $transactionId,
            'amount_cents' => $amountCents,
            'currency' => $currency,
            'customer_email' => $customerEmail,
        ]);

        $payment->analysis->unlock($transactionId);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function handleTransactionFailed(array $data): void
    {
        $transactionData = $data['data'] ?? [];
        $transactionId = $transactionData['id'] ?? '';

        $payment = Payment::where('paddle_transaction_id', $transactionId)->first();

        $payment?->markAsFailed();
    }

    public function verifyPayment(string $transactionId): bool
    {
        try {
            $transaction = $this->client->transactions->get($transactionId);

            return $transaction->status->getValue() === 'completed';
        } catch (ApiError|SdkException) {
            return false;
        }
    }

    /**
     * Get transaction details from Paddle.
     */
    public function getTransaction(string $transactionId): ?Transaction
    {
        try {
            return $this->client->transactions->get($transactionId);
        } catch (ApiError|SdkException) {
            return null;
        }
    }
}
