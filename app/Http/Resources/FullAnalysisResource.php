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
        /** @var Analysis $this */
        return [
            'id' => $this->uuid,
            'username' => $this->github_username,
            'status' => $this->status->value,
            'overall_score' => $this->overall_score,
            'score_level' => [
                'name' => $this->score_level->value,
                'label' => $this->score_level->label(),
                'color' => $this->score_level->color(),
                'description' => $this->score_level->description(),
            ],
            'category_scores' => $this->category_scores,
            'summary' => $this->ai_analysis['summary'] ?? null,
            'first_impression' => $this->ai_analysis['first_impression'] ?? null,
            'recruiter_perspective' => $this->ai_analysis['recruiter_perspective'] ?? null,
            'categories' => $this->ai_analysis['categories'] ?? [],
            'deal_breakers' => $this->ai_analysis['deal_breakers'] ?? [],
            'strengths' => $this->ai_analysis['strengths'] ?? [],
            'top_projects_analysis' => $this->ai_analysis['top_projects_analysis'] ?? [],
            'improvement_checklist' => $this->ai_analysis['improvement_checklist'] ?? [],
            'github_data' => $this->github_data,
            'is_paid' => $this->is_paid,
            'created_at' => $this->created_at?->toIso8601String(),
            'completed_at' => $this->completed_at?->toIso8601String(),
        ];
    }
}
