<?php

declare(strict_types=1);

namespace App\DTOs\Payment;

use Spatie\LaravelData\Data;

class CheckoutSessionDTO extends Data
{
    public function __construct(
        public readonly string $sessionId,
        public readonly string $checkoutUrl,
    ) {}
}
