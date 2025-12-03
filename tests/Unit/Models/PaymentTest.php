<?php

declare(strict_types=1);

use App\Enums\PaymentStatus;
use App\Models\Analysis;
use App\Models\Payment;

describe('Payment model', function (): void {
    it('belongs to an analysis', function (): void {
        $analysis = Analysis::factory()->create();
        $payment = Payment::factory()->create(['analysis_id' => $analysis->id]);

        expect($payment->analysis)->toBeInstanceOf(Analysis::class);
        expect($payment->analysis->id)->toBe($analysis->id);
    });

    it('casts status to PaymentStatus enum', function (): void {
        $payment = Payment::factory()->create(['status' => 'pending']);

        expect($payment->status)->toBeInstanceOf(PaymentStatus::class);
        expect($payment->status)->toBe(PaymentStatus::PENDING);
    });

    it('formats amount correctly', function (): void {
        $payment = Payment::factory()->create(['amount_cents' => 999]);

        expect($payment->getAmountFormatted())->toBe('$9.99');
    });

    it('formats large amounts correctly', function (): void {
        $payment = Payment::factory()->create(['amount_cents' => 10000]);

        expect($payment->getAmountFormatted())->toBe('$100.00');
    });

    it('scopes to completed payments', function (): void {
        Payment::factory()->count(3)->completed()->create();
        Payment::factory()->count(2)->pending()->create();

        expect(Payment::completed()->count())->toBe(3);
    });

    it('scopes to pending payments', function (): void {
        Payment::factory()->count(3)->completed()->create();
        Payment::factory()->count(2)->pending()->create();

        expect(Payment::pending()->count())->toBe(2);
    });

    it('scopes to failed payments', function (): void {
        Payment::factory()->count(2)->failed()->create();
        Payment::factory()->count(3)->completed()->create();

        expect(Payment::failed()->count())->toBe(2);
    });

    it('marks payment as failed', function (): void {
        $payment = Payment::factory()->pending()->create();

        $payment->markAsFailed();

        expect($payment->fresh()->status)->toBe(PaymentStatus::FAILED);
    });
});
