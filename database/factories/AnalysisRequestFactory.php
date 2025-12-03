<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\AnalysisRequest;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AnalysisRequest>
 */
class AnalysisRequestFactory extends Factory
{
    protected $model = AnalysisRequest::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'ip_address' => fake()->ipv4(),
            'request_count' => fake()->numberBetween(1, 10),
            'first_request_at' => now()->subHours(fake()->numberBetween(1, 24)),
            'last_request_at' => now(),
        ];
    }
}
