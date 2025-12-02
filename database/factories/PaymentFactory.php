<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\PaymentStatus;
use App\Models\Analysis;
use App\Models\Payment;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Payment>
 */
class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'analysis_id' => Analysis::factory(),
            'stripe_session_id' => 'cs_'.Str::random(24),
            'stripe_payment_intent' => null,
            'amount_cents' => 999,
            'currency' => 'USD',
            'status' => PaymentStatus::PENDING,
            'customer_email' => null,
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => PaymentStatus::PENDING,
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => PaymentStatus::COMPLETED,
            'stripe_payment_intent' => 'pi_'.Str::random(24),
            'customer_email' => fake()->email(),
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => PaymentStatus::FAILED,
        ]);
    }
}
