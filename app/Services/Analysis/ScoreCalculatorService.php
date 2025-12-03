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
            $categoryData = $result->categories[$category] ?? [];
            $score = isset($categoryData['score']) && is_numeric($categoryData['score'])
                ? (int) $categoryData['score']
                : 0;
            $weightedSum += $score * $weight;
        }

        return (int) round($weightedSum);
    }

    /**
     * @return array<string, int>
     */
    public function extractCategoryScores(AnalysisResultDTO $result): array
    {
        return [
            'profile' => $this->getCategoryScore($result->categories, 'profile_completeness'),
            'projects' => $this->getCategoryScore($result->categories, 'project_quality'),
            'consistency' => $this->getCategoryScore($result->categories, 'contribution_consistency'),
            'technical' => $this->getCategoryScore($result->categories, 'technical_signals'),
            'community' => $this->getCategoryScore($result->categories, 'community_engagement'),
        ];
    }

    /**
     * @param  array<string, array<string, mixed>>  $categories
     */
    private function getCategoryScore(array $categories, string $key): int
    {
        $category = $categories[$key] ?? [];
        $score = $category['score'] ?? 0;

        return is_numeric($score) ? (int) $score : 0;
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
