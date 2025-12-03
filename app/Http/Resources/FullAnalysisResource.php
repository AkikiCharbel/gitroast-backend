<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Analysis;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Analysis
 */
class FullAnalysisResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var Analysis $analysis */
        $analysis = $this->resource;

        /** @var array<string, mixed> $aiAnalysis */
        $aiAnalysis = is_array($analysis->ai_analysis) ? $analysis->ai_analysis : [];

        return [
            'id' => $analysis->uuid,
            'username' => $analysis->github_username,
            'status' => $analysis->status->value,
            'overall_score' => $analysis->overall_score,
            'score_level' => [
                'name' => $analysis->score_level->value,
                'label' => $analysis->score_level->label(),
                'color' => $analysis->score_level->color(),
                'description' => $analysis->score_level->description(),
            ],
            'category_scores' => $analysis->category_scores,
            'summary' => $aiAnalysis['summary'] ?? null,
            'first_impression' => $aiAnalysis['first_impression'] ?? null,
            'recruiter_perspective' => $aiAnalysis['recruiter_perspective'] ?? null,
            'categories' => $aiAnalysis['categories'] ?? [],
            'deal_breakers' => $aiAnalysis['deal_breakers'] ?? [],
            'strengths' => $aiAnalysis['strengths'] ?? [],
            'top_projects_analysis' => $aiAnalysis['top_projects_analysis'] ?? [],
            'improvement_checklist' => $aiAnalysis['improvement_checklist'] ?? [],
            'github_data' => $analysis->github_data,
            'is_paid' => $analysis->is_paid,
            'created_at' => $analysis->created_at->toIso8601String(),
            'completed_at' => $analysis->completed_at?->toIso8601String(),
        ];
    }
}
