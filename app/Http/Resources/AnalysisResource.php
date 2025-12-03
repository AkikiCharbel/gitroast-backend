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
        /** @var Analysis $analysis */
        $analysis = $this->resource;
        $isPaid = $analysis->is_paid;
        $isCompleted = $analysis->status->value === 'completed';

        /** @var array<string, mixed> $aiAnalysis */
        $aiAnalysis = is_array($analysis->ai_analysis) ? $analysis->ai_analysis : [];

        /** @var array<int, mixed> $dealBreakers */
        $dealBreakers = isset($aiAnalysis['deal_breakers']) && is_array($aiAnalysis['deal_breakers'])
            ? $aiAnalysis['deal_breakers']
            : [];

        /** @var array<int, mixed> $strengths */
        $strengths = isset($aiAnalysis['strengths']) && is_array($aiAnalysis['strengths'])
            ? $aiAnalysis['strengths']
            : [];

        return [
            'id' => $analysis->uuid,
            'username' => $analysis->github_username,
            'status' => $analysis->status->value,
            'overall_score' => $analysis->overall_score,
            'score_level' => [
                'name' => $analysis->score_level->value,
                'label' => $analysis->score_level->label(),
                'color' => $analysis->score_level->color(),
            ],
            'category_scores' => $this->when($isCompleted, fn () => $analysis->category_scores),
            'summary' => $this->when($isCompleted, fn () => $aiAnalysis['summary'] ?? null),
            'first_impression' => $this->when($isCompleted, fn () => $aiAnalysis['first_impression'] ?? null),
            'deal_breakers' => $this->when(
                $isCompleted,
                fn () => $isPaid ? $dealBreakers : array_slice($dealBreakers, 0, 3)
            ),
            'strengths' => $this->when(
                $isCompleted,
                fn () => $isPaid ? $strengths : array_slice($strengths, 0, 2)
            ),
            'improvement_checklist' => $this->when(
                $isPaid && $isCompleted,
                fn () => $aiAnalysis['improvement_checklist'] ?? []
            ),
            'is_paid' => $isPaid,
            'created_at' => $analysis->created_at->toIso8601String(),
            'completed_at' => $analysis->completed_at?->toIso8601String(),
        ];
    }
}
