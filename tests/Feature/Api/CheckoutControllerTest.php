<?php

declare(strict_types=1);

use App\Models\Analysis;
use App\Services\Payment\PaymentService;

describe('POST /api/checkout/create', function (): void {
    it('returns error for unpaid analysis that is not complete', function (): void {
        $analysis = Analysis::factory()->pending()->create();

        $response = $this->postJson('/api/checkout/create', [
            'analysis_id' => $analysis->uuid,
        ]);

        $response->assertStatus(400)
            ->assertJsonPath('message', 'Analysis must be completed before checkout.');
    });

    it('returns error for already paid analysis', function (): void {
        $analysis = Analysis::factory()->paid()->create();

        $response = $this->postJson('/api/checkout/create', [
            'analysis_id' => $analysis->uuid,
        ]);

        $response->assertStatus(400)
            ->assertJsonPath('message', 'Analysis is already paid.');
    });

    it('returns 404 for non-existent analysis', function (): void {
        $response = $this->postJson('/api/checkout/create', [
            'analysis_id' => 'non-existent-uuid',
        ]);

        $response->assertStatus(404);
    });

    it('validates analysis_id is required', function (): void {
        $response = $this->postJson('/api/checkout/create', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['analysis_id']);
    });

    it('creates checkout session for completed unpaid analysis', function (): void {
        $analysis = Analysis::factory()->completed()->create(['is_paid' => false]);

        // Mock the payment service
        $this->mock(PaymentService::class, function ($mock) {
            $mock->shouldReceive('createCheckoutSession')
                ->once()
                ->andReturn(new \App\DTOs\Payment\CheckoutSessionDTO(
                    sessionId: 'txn_test123',
                    checkoutUrl: 'https://checkout.paddle.com/test'
                ));
        });

        $response = $this->postJson('/api/checkout/create', [
            'analysis_id' => $analysis->uuid,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => ['transaction_id', 'checkout_url'],
            ]);
    });
});

describe('GET /api/checkout/verify/{transactionId}', function (): void {
    it('returns is_paid false for invalid transaction', function (): void {
        $this->mock(PaymentService::class, function ($mock) {
            $mock->shouldReceive('verifyPayment')
                ->with('invalid-transaction')
                ->once()
                ->andReturn(false);
        });

        $response = $this->getJson('/api/checkout/verify/invalid-transaction');

        $response->assertStatus(200)
            ->assertJsonPath('data.is_paid', false);
    });

    it('returns is_paid true for valid completed transaction', function (): void {
        $this->mock(PaymentService::class, function ($mock) {
            $mock->shouldReceive('verifyPayment')
                ->with('valid-transaction')
                ->once()
                ->andReturn(true);
        });

        $response = $this->getJson('/api/checkout/verify/valid-transaction');

        $response->assertStatus(200)
            ->assertJsonPath('data.is_paid', true);
    });
});
