<?php

declare(strict_types=1);

namespace App\DTOs\Analysis;

use Spatie\LaravelData\Data;

class AnalysisResultDTO extends Data
{
    /**
     * @param  array<string, array<string, mixed>>  $categories
     * @param  array<int, array<string, mixed>>  $dealBreakers
     * @param  array<int, array<string, mixed>>  $topProjectsAnalysis
     * @param  array<int, array<string, mixed>>  $improvementChecklist
     * @param  array<int, string>  $strengths
     */
    public function __construct(
        public readonly int $overallScore,
        public readonly string $summary,
        public readonly string $firstImpression,
        public readonly array $categories,
        public readonly array $dealBreakers,
        public readonly array $topProjectsAnalysis,
        public readonly array $improvementChecklist,
        public readonly array $strengths,
        public readonly ?string $recruiterPerspective,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        $overallScore = isset($data['overall_score']) ? (int) $data['overall_score'] : 0;
        $summary = isset($data['summary']) && is_string($data['summary']) ? $data['summary'] : '';
        $firstImpression = isset($data['first_impression']) && is_string($data['first_impression']) ? $data['first_impression'] : '';

        /** @var array<string, array<string, mixed>> $categories */
        $categories = isset($data['categories']) && is_array($data['categories']) ? $data['categories'] : [];

        /** @var array<int, array<string, mixed>> $dealBreakers */
        $dealBreakers = isset($data['deal_breakers']) && is_array($data['deal_breakers']) ? $data['deal_breakers'] : [];

        /** @var array<int, array<string, mixed>> $topProjectsAnalysis */
        $topProjectsAnalysis = isset($data['top_projects_analysis']) && is_array($data['top_projects_analysis']) ? $data['top_projects_analysis'] : [];

        /** @var array<int, array<string, mixed>> $improvementChecklist */
        $improvementChecklist = isset($data['improvement_checklist']) && is_array($data['improvement_checklist']) ? $data['improvement_checklist'] : [];

        /** @var array<int, string> $strengths */
        $strengths = isset($data['strengths']) && is_array($data['strengths']) ? $data['strengths'] : [];

        $recruiterPerspective = isset($data['recruiter_perspective']) && is_string($data['recruiter_perspective']) ? $data['recruiter_perspective'] : null;

        return new self(
            overallScore: $overallScore,
            summary: $summary,
            firstImpression: $firstImpression,
            categories: $categories,
            dealBreakers: $dealBreakers,
            topProjectsAnalysis: $topProjectsAnalysis,
            improvementChecklist: $improvementChecklist,
            strengths: $strengths,
            recruiterPerspective: $recruiterPerspective,
        );
    }
}
