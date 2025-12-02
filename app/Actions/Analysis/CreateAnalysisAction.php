<?php

declare(strict_types=1);

namespace App\Actions\Analysis;

use App\DTOs\Analysis\CreateAnalysisDTO;
use App\Jobs\ProcessAnalysisJob;
use App\Models\Analysis;
use Illuminate\Support\Str;

class CreateAnalysisAction
{
    public function execute(CreateAnalysisDTO $dto): Analysis
    {
        $analysis = Analysis::create([
            'uuid' => Str::uuid()->toString(),
            'github_username' => strtolower($dto->username),
            'status' => 'pending',
            'ip_address' => $dto->ipAddress,
        ]);

        ProcessAnalysisJob::dispatch($analysis);

        return $analysis;
    }
}
