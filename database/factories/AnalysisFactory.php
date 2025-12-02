<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Enums\AnalysisStatus;
use App\Models\Analysis;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Analysis>
 */
class AnalysisFactory extends Factory
{
    protected $model = Analysis::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'uuid' => Str::uuid()->toString(),
            'github_username' => fake()->userName(),
            'status' => AnalysisStatus::PENDING,
            'overall_score' => null,
            'profile_score' => null,
            'projects_score' => null,
            'consistency_score' => null,
            'technical_score' => null,
            'community_score' => null,
            'github_data' => null,
            'ai_analysis' => null,
            'is_paid' => false,
            'stripe_payment_id' => null,
            'paid_at' => null,
            'ip_address' => fake()->ipv4(),
            'error_message' => null,
            'completed_at' => null,
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => AnalysisStatus::PENDING,
        ]);
    }

    public function processing(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => AnalysisStatus::PROCESSING,
        ]);
    }

    public function completed(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => AnalysisStatus::COMPLETED,
            'overall_score' => fake()->numberBetween(30, 95),
            'profile_score' => fake()->numberBetween(30, 100),
            'projects_score' => fake()->numberBetween(30, 100),
            'consistency_score' => fake()->numberBetween(30, 100),
            'technical_score' => fake()->numberBetween(30, 100),
            'community_score' => fake()->numberBetween(30, 100),
            'github_data' => [
                'user' => [
                    'name' => fake()->name(),
                    'bio' => fake()->sentence(),
                    'location' => fake()->city(),
                    'followers' => fake()->numberBetween(0, 1000),
                    'following' => fake()->numberBetween(0, 500),
                    'public_repos' => fake()->numberBetween(1, 100),
                ],
                'has_profile_readme' => fake()->boolean(),
                'top_repos_count' => fake()->numberBetween(1, 15),
            ],
            'ai_analysis' => [
                'summary' => fake()->paragraph(),
                'first_impression' => fake()->sentence(),
                'categories' => [
                    'profile_completeness' => [
                        'score' => fake()->numberBetween(30, 100),
                        'issues' => [fake()->sentence()],
                        'recommendations' => [fake()->sentence()],
                        'details' => fake()->paragraph(),
                    ],
                    'project_quality' => [
                        'score' => fake()->numberBetween(30, 100),
                        'issues' => [fake()->sentence()],
                        'recommendations' => [fake()->sentence()],
                        'details' => fake()->paragraph(),
                    ],
                    'contribution_consistency' => [
                        'score' => fake()->numberBetween(30, 100),
                        'issues' => [fake()->sentence()],
                        'recommendations' => [fake()->sentence()],
                        'details' => fake()->paragraph(),
                    ],
                    'technical_signals' => [
                        'score' => fake()->numberBetween(30, 100),
                        'issues' => [fake()->sentence()],
                        'recommendations' => [fake()->sentence()],
                        'details' => fake()->paragraph(),
                    ],
                    'community_engagement' => [
                        'score' => fake()->numberBetween(30, 100),
                        'issues' => [fake()->sentence()],
                        'recommendations' => [fake()->sentence()],
                        'details' => fake()->paragraph(),
                    ],
                ],
                'deal_breakers' => [
                    [
                        'issue' => fake()->sentence(),
                        'why_it_matters' => fake()->sentence(),
                        'fix' => fake()->sentence(),
                    ],
                ],
                'top_projects_analysis' => [],
                'improvement_checklist' => [
                    [
                        'priority' => fake()->randomElement(['high', 'medium', 'low']),
                        'task' => fake()->sentence(),
                        'time_estimate' => '10 minutes',
                        'impact' => fake()->sentence(),
                    ],
                ],
                'strengths' => [fake()->sentence()],
                'recruiter_perspective' => fake()->paragraph(),
            ],
            'completed_at' => now(),
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn (array $attributes): array => [
            'status' => AnalysisStatus::FAILED,
            'error_message' => fake()->sentence(),
        ]);
    }

    public function paid(): static
    {
        return $this->completed()->state(fn (array $attributes): array => [
            'is_paid' => true,
            'stripe_payment_id' => 'pi_'.Str::random(24),
            'paid_at' => now(),
        ]);
    }
}
