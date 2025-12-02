<?php

declare(strict_types=1);

namespace App\DTOs\Analysis;

use Spatie\LaravelData\Data;

class CreateAnalysisDTO extends Data
{
    public function __construct(
        public readonly string $username,
        public readonly ?string $ipAddress = null,
    ) {}
}
