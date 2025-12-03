<?php

declare(strict_types=1);

use App\Enums\PaymentStatus;

describe('PaymentStatus enum', function (): void {
    it('has correct values', function (): void {
        expect(PaymentStatus::PENDING->value)->toBe('pending');
        expect(PaymentStatus::COMPLETED->value)->toBe('completed');
        expect(PaymentStatus::FAILED->value)->toBe('failed');
        expect(PaymentStatus::REFUNDED->value)->toBe('refunded');
    });

    it('returns correct labels', function (): void {
        expect(PaymentStatus::PENDING->label())->toBe('Pending');
        expect(PaymentStatus::COMPLETED->label())->toBe('Completed');
        expect(PaymentStatus::FAILED->label())->toBe('Failed');
        expect(PaymentStatus::REFUNDED->label())->toBe('Refunded');
    });

    it('returns correct colors', function (): void {
        expect(PaymentStatus::PENDING->color())->toBe('warning');
        expect(PaymentStatus::COMPLETED->color())->toBe('success');
        expect(PaymentStatus::FAILED->color())->toBe('danger');
        expect(PaymentStatus::REFUNDED->color())->toBe('info');
    });
});
