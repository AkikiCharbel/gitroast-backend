<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Analysis;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Analysis
 */
class AnalysisResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var Analysis $this */
        $isPaid = $this->is_paid;

        return [
            'id' => $this->uuid,
            'username' => $this->github_username,
            'status' => $this->status->value,
            'overall_score' => $this->overall_score,
            'score_level' => [
                'name' => $this->score_level->value,
                'label' => $this->score_level->label(),
                'color' => $this->score_level->color(),
            ],
            'category_scores' => $this->when(
                $this->status->value === 'completed',
                $this->category_scores
            ),
            'summary' => $this->when(
                $this->status->value === 'completed',
                $this->ai_analysis['summary'] ?? null
            ),
            'first_impression' => $this->when(
                $this->status->value === 'completed',
                $this->ai_analysis['first_impression'] ?? null
            ),
            'deal_breakers' => $this->when(
                $this->status->value === 'completed',
                fn () => $isPaid
                    ? ($this->ai_analysis['deal_breakers'] ?? [])
                    : array_slice($this->ai_analysis['deal_breakers'] ?? [], 0, 3)
            ),
            'strengths' => $this->when(
                $this->status->value === 'completed',
                fn () => $isPaid
                    ? ($this->ai_analysis['strengths'] ?? [])
                    : array_slice($this->ai_analysis['strengths'] ?? [], 0, 2)
            ),
            'improvement_checklist' => $this->when(
                $isPaid && $this->status->value === 'completed',
                $this->ai_analysis['improvement_checklist'] ?? []
            ),
            'is_paid' => $isPaid,
            'created_at' => $this->created_at?->toIso8601String(),
            'completed_at' => $this->completed_at?->toIso8601String(),
        ];
    }
}
