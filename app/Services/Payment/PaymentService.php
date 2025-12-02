<?php

declare(strict_types=1);

namespace App\Services\Payment;

use App\DTOs\Payment\CheckoutSessionDTO;
use App\Exceptions\PaymentException;
use App\Models\Analysis;
use App\Models\Payment;
use Stripe\Checkout\Session;
use Stripe\Exception\ApiErrorException;
use Stripe\Stripe;
use Stripe\Webhook;

class PaymentService
{
    public function __construct()
    {
        Stripe::setApiKey(config('services.stripe.secret', ''));
    }

    public function createCheckoutSession(Analysis $analysis): CheckoutSessionDTO
    {
        if ($analysis->is_paid) {
            throw new PaymentException('Analysis is already paid');
        }

        try {
            $session = Session::create([
                'payment_method_types' => ['card'],
                'line_items' => [[
                    'price' => config('services.stripe.price_full_report'),
                    'quantity' => 1,
                ]],
                'mode' => 'payment',
                'success_url' => config('app.frontend_url').'/success?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => config('app.frontend_url')."/analyze/{$analysis->uuid}",
                'metadata' => [
                    'analysis_id' => $analysis->id,
                    'analysis_uuid' => $analysis->uuid,
                    'github_username' => $analysis->github_username,
                ],
                'client_reference_id' => $analysis->uuid,
            ]);

            Payment::create([
                'analysis_id' => $analysis->id,
                'stripe_session_id' => $session->id,
                'amount_cents' => $session->amount_total ?? 0,
                'currency' => strtoupper($session->currency ?? 'USD'),
                'status' => 'pending',
            ]);

            return new CheckoutSessionDTO(
                sessionId: $session->id,
                checkoutUrl: $session->url ?? '',
            );
        } catch (ApiErrorException $e) {
            throw new PaymentException(
                "Failed to create checkout session: {$e->getMessage()}",
                previous: $e
            );
        }
    }

    public function handleWebhook(string $payload, string $signature): void
    {
        try {
            $event = Webhook::constructEvent(
                $payload,
                $signature,
                config('services.stripe.webhook_secret', '')
            );
        } catch (\Exception $e) {
            throw new PaymentException("Webhook signature verification failed: {$e->getMessage()}");
        }

        match ($event->type) {
            'checkout.session.completed' => $this->handleCheckoutCompleted($event->data->object),
            'payment_intent.succeeded' => $this->handlePaymentSucceeded($event->data->object),
            'payment_intent.payment_failed' => $this->handlePaymentFailed($event->data->object),
            default => null,
        };
    }

    private function handleCheckoutCompleted(Session $session): void
    {
        $payment = Payment::where('stripe_session_id', $session->id)->first();

        if (! $payment) {
            return;
        }

        $payment->update([
            'status' => 'completed',
            'stripe_payment_intent' => $session->payment_intent,
            'customer_email' => $session->customer_details?->email,
        ]);

        $payment->analysis->unlock((string) $session->payment_intent);
    }

    private function handlePaymentSucceeded(object $paymentIntent): void
    {
        /** @var string $paymentIntentId */
        $paymentIntentId = $paymentIntent->id;

        $payment = Payment::where('stripe_payment_intent', $paymentIntentId)->first();

        if ($payment && $payment->status->value !== 'completed') {
            $payment->markAsCompleted($paymentIntentId);
        }
    }

    private function handlePaymentFailed(object $paymentIntent): void
    {
        /** @var string $paymentIntentId */
        $paymentIntentId = $paymentIntent->id;

        $payment = Payment::where('stripe_payment_intent', $paymentIntentId)->first();

        $payment?->markAsFailed();
    }

    public function verifyPayment(string $sessionId): bool
    {
        try {
            $session = Session::retrieve($sessionId);

            return $session->payment_status === 'paid';
        } catch (ApiErrorException) {
            return false;
        }
    }
}
