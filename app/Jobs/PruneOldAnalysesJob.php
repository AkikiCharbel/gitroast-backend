<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Analysis;
use App\Models\AnalysisRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class PruneOldAnalysesJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public function __construct(
        public int $daysToKeep = 90
    ) {
        $this->onQueue('low');
    }

    public function handle(): void
    {
        $deletedAnalyses = Analysis::query()
            ->where('is_paid', false)
            ->where('created_at', '<', now()->subDays($this->daysToKeep))
            ->delete();

        $deletedRequests = AnalysisRequest::pruneOld(24);

        Log::info('Pruned old data', [
            'analyses' => $deletedAnalyses,
            'requests' => $deletedRequests,
        ]);
    }
}
