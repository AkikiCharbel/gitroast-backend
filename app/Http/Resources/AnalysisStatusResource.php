<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Analysis;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Analysis
 */
class AnalysisStatusResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        /** @var Analysis $analysis */
        $analysis = $this->resource;

        return [
            'id' => $analysis->uuid,
            'status' => $analysis->status->value,
            'progress' => $analysis->status->progress(),
            'redirect' => $this->when(
                $analysis->status->value === 'completed',
                fn () => "/analysis/{$analysis->uuid}"
            ),
            'error' => $this->when(
                $analysis->status->value === 'failed',
                fn () => $analysis->error_message
            ),
        ];
    }
}
