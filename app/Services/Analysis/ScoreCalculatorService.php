<?php

declare(strict_types=1);

namespace App\Services\Analysis;

use App\DTOs\Analysis\AnalysisResultDTO;
use App\Enums\ScoreCategory;

class ScoreCalculatorService
{
    /**
     * @var array<string, float>
     */
    private const array WEIGHTS = [
        'profile_completeness' => 0.15,
        'project_quality' => 0.30,
        'contribution_consistency' => 0.20,
        'technical_signals' => 0.20,
        'community_engagement' => 0.15,
    ];

    public function calculateOverallScore(AnalysisResultDTO $result): int
    {
        $weightedSum = 0.0;

        foreach (self::WEIGHTS as $category => $weight) {
            $score = $result->categories[$category]['score'] ?? 0;
            $weightedSum += (int) $score * $weight;
        }

        return (int) round($weightedSum);
    }

    /**
     * @return array<string, int>
     */
    public function extractCategoryScores(AnalysisResultDTO $result): array
    {
        return [
            'profile' => (int) ($result->categories['profile_completeness']['score'] ?? 0),
            'projects' => (int) ($result->categories['project_quality']['score'] ?? 0),
            'consistency' => (int) ($result->categories['contribution_consistency']['score'] ?? 0),
            'technical' => (int) ($result->categories['technical_signals']['score'] ?? 0),
            'community' => (int) ($result->categories['community_engagement']['score'] ?? 0),
        ];
    }

    public function normalizeScore(int $score): int
    {
        return max(0, min(100, $score));
    }

    /**
     * @param  array<int, int>  $historicalScores
     */
    public function calculateTrend(array $historicalScores): string
    {
        if (count($historicalScores) < 2) {
            return 'stable';
        }

        $recent = array_slice($historicalScores, -3);
        $average = array_sum($recent) / count($recent);
        $oldest = $historicalScores[0];

        $change = $average - $oldest;

        return match (true) {
            $change > 5 => 'improving',
            $change < -5 => 'declining',
            default => 'stable',
        };
    }

    /**
     * @return array<string, array{label: string, weight: float, description: string}>
     */
    public function getCategoryInfo(): array
    {
        $info = [];
        foreach (ScoreCategory::all() as $category) {
            $info[$category->value] = [
                'label' => $category->label(),
                'weight' => $category->weight(),
                'description' => $category->description(),
            ];
        }

        return $info;
    }
}
