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
        /** @var Analysis $this */
        return [
            'id' => $this->uuid,
            'status' => $this->status->value,
            'progress' => $this->status->progress(),
            'redirect' => $this->when(
                $this->status->value === 'completed',
                "/analysis/{$this->uuid}"
            ),
            'error' => $this->when(
                $this->status->value === 'failed',
                $this->error_message
            ),
        ];
    }
}
