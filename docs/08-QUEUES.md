# Queues & Jobs Documentation

Complete guide to background job processing for GitRoast.

---

## Table of Contents

1. [Queue Configuration](#queue-configuration)
2. [Job Classes](#job-classes)
3. [Job Chains](#job-chains)
4. [Horizon Setup](#horizon-setup)
5. [Error Handling](#error-handling)
6. [Monitoring](#monitoring)

---

## Queue Configuration

### Redis Queue Setup

**config/queue.php:**

```php
<?php

return [
    'default' => env('QUEUE_CONNECTION', 'redis'),

    'connections' => [
        'redis' => [
            'driver' => 'redis',
            'connection' => 'default',
            'queue' => env('REDIS_QUEUE', 'default'),
            'retry_after' => 90,
            'block_for' => null,
            'after_commit' => false,
        ],
    ],

    'batching' => [
        'database' => env('DB_CONNECTION', 'mysql'),
        'table' => 'job_batches',
    ],

    'failed' => [
        'driver' => env('QUEUE_FAILED_DRIVER', 'database-uuids'),
        'database' => env('DB_CONNECTION', 'mysql'),
        'table' => 'failed_jobs',
    ],
];
```

### Queue Names

| Queue | Purpose | Priority |
|-------|---------|----------|
| `high` | Payment processing | 1 (highest) |
| `default` | Analysis processing | 2 |
| `low` | Notifications, cleanup | 3 (lowest) |

---

## Job Classes

### ProcessAnalysisJob

The main job that orchestrates the entire analysis flow.

```php
<?php

namespace App\Jobs;

use App\Enums\AnalysisStatus;
use App\Events\AnalysisCompleted;
use App\Events\AnalysisFailed;
use App\Exceptions\AIAnalysisException;
use App\Exceptions\GitHubApiException;
use App\Models\Analysis;
use App\Services\AI\AIAnalysisService;
use App\Services\Analysis\ScoreCalculatorService;
use App\Services\GitHub\GitHubService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProcessAnalysisJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * Backoff intervals in seconds.
     */
    public array $backoff = [30, 60, 120];

    /**
     * Maximum execution time in seconds.
     */
    public int $timeout = 180;

    /**
     * Delete the job if its models no longer exist.
     */
    public bool $deleteWhenMissingModels = true;

    public function __construct(
        public Analysis $analysis
    ) {
        $this->onQueue('default');
    }

    public function handle(
        GitHubService $github,
        AIAnalysisService $ai,
        ScoreCalculatorService $calculator
    ): void {
        Log::info('Processing analysis', ['id' => $this->analysis->id]);

        try {
            // Mark as processing
            $this->analysis->markAsProcessing();

            // Step 1: Fetch GitHub data
            $profileData = $github->getProfileData($this->analysis->github_username);

            // Store raw GitHub data
            $this->analysis->update([
                'github_data' => [
                    'user' => [
                        'name' => $profileData->name,
                        'bio' => $profileData->bio,
                        'location' => $profileData->location,
                        'followers' => $profileData->followers,
                        'following' => $profileData->following,
                        'public_repos' => $profileData->publicRepos,
                    ],
                    'has_profile_readme' => !empty($profileData->profileReadme),
                    'top_repos_count' => $profileData->repositories->count(),
                ],
            ]);

            // Step 2: Run AI analysis
            $analysisResult = $ai->analyze($profileData);

            // Step 3: Calculate scores
            $categoryScores = $calculator->extractCategoryScores($analysisResult);
            $overallScore = $calculator->calculateOverallScore($analysisResult);

            // Step 4: Store results
            $this->analysis->markAsCompleted([
                'overall_score' => $overallScore,
                'profile_score' => $categoryScores['profile'],
                'projects_score' => $categoryScores['projects'],
                'consistency_score' => $categoryScores['consistency'],
                'technical_score' => $categoryScores['technical'],
                'community_score' => $categoryScores['community'],
                'ai_analysis' => [
                    'summary' => $analysisResult->summary,
                    'first_impression' => $analysisResult->firstImpression,
                    'categories' => $analysisResult->categories,
                    'deal_breakers' => $analysisResult->dealBreakers,
                    'top_projects_analysis' => $analysisResult->topProjectsAnalysis,
                    'improvement_checklist' => $analysisResult->improvementChecklist,
                    'strengths' => $analysisResult->strengths,
                    'recruiter_perspective' => $analysisResult->recruiterPerspective,
                ],
            ]);

            // Dispatch completion event
            event(new AnalysisCompleted($this->analysis));

            Log::info('Analysis completed', [
                'id' => $this->analysis->id,
                'score' => $overallScore,
            ]);

        } catch (GitHubApiException $e) {
            $this->handleFailure($e, 'GitHub API error');
        } catch (AIAnalysisException $e) {
            $this->handleFailure($e, 'AI analysis error');
        } catch (\Throwable $e) {
            $this->handleFailure($e, 'Unexpected error');
        }
    }

    /**
     * Handle job failure.
     */
    private function handleFailure(\Throwable $e, string $context): void
    {
        Log::error("Analysis failed: {$context}", [
            'id' => $this->analysis->id,
            'username' => $this->analysis->github_username,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);

        // Only mark as failed on final attempt
        if ($this->attempts() >= $this->tries) {
            $this->analysis->markAsFailed("{$context}: {$e->getMessage()}");
            event(new AnalysisFailed($this->analysis, $e));
        }

        throw $e; // Re-throw to trigger retry
    }

    /**
     * Handle a job that has failed after all retries.
     */
    public function failed(\Throwable $exception): void
    {
        $this->analysis->markAsFailed($exception->getMessage());
        event(new AnalysisFailed($this->analysis, $exception));

        Log::critical('Analysis job permanently failed', [
            'id' => $this->analysis->id,
            'username' => $this->analysis->github_username,
            'error' => $exception->getMessage(),
        ]);
    }

    /**
     * Calculate backoff for retries.
     */
    public function retryUntil(): \DateTime
    {
        return now()->addMinutes(10);
    }

    /**
     * Get unique job identifier.
     */
    public function uniqueId(): string
    {
        return $this->analysis->uuid;
    }

    /**
     * Tags for monitoring.
     */
    public function tags(): array
    {
        return [
            'analysis',
            'username:' . $this->analysis->github_username,
        ];
    }
}
```

### FetchGitHubDataJob

Separate job for GitHub data fetching (if using job chains).

```php
<?php

namespace App\Jobs;

use App\Models\Analysis;
use App\Services\GitHub\GitHubService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class FetchGitHubDataJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public array $backoff = [10, 30, 60];

    public function __construct(
        public Analysis $analysis
    ) {
        $this->onQueue('default');
    }

    public function handle(GitHubService $github): void
    {
        $profileData = $github->getProfileData($this->analysis->github_username);

        $this->analysis->update([
            'github_data' => $profileData->toArray(),
        ]);
    }
}
```

### RunAIAnalysisJob

Separate job for AI analysis (if using job chains).

```php
<?php

namespace App\Jobs;

use App\DTOs\Analysis\GitHubProfileDTO;
use App\Models\Analysis;
use App\Services\AI\AIAnalysisService;
use App\Services\Analysis\ScoreCalculatorService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RunAIAnalysisJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;
    public int $timeout = 120;

    public function __construct(
        public Analysis $analysis
    ) {
        $this->onQueue('default');
    }

    public function handle(
        AIAnalysisService $ai,
        ScoreCalculatorService $calculator
    ): void {
        $profileData = GitHubProfileDTO::from($this->analysis->github_data);

        $result = $ai->analyze($profileData);

        $scores = $calculator->extractCategoryScores($result);

        $this->analysis->markAsCompleted([
            'overall_score' => $calculator->calculateOverallScore($result),
            'profile_score' => $scores['profile'],
            'projects_score' => $scores['projects'],
            'consistency_score' => $scores['consistency'],
            'technical_score' => $scores['technical'],
            'community_score' => $scores['community'],
            'ai_analysis' => $result->toArray(),
        ]);
    }
}
```

### PruneOldAnalysesJob

Cleanup job for old data.

```php
<?php

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
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public int $daysToKeep = 90
    ) {
        $this->onQueue('low');
    }

    public function handle(): void
    {
        // Delete old unpaid analyses
        $deletedAnalyses = Analysis::query()
            ->where('is_paid', false)
            ->where('created_at', '<', now()->subDays($this->daysToKeep))
            ->delete();

        // Prune rate limiting records
        $deletedRequests = AnalysisRequest::pruneOld(24);

        Log::info('Pruned old data', [
            'analyses' => $deletedAnalyses,
            'requests' => $deletedRequests,
        ]);
    }
}
```

---

## Job Chains

### Using Job Chains for Complex Flows

```php
<?php

namespace App\Actions\Analysis;

use App\Jobs\FetchGitHubDataJob;
use App\Jobs\RunAIAnalysisJob;
use App\Jobs\SendAnalysisCompletedNotificationJob;
use App\Models\Analysis;
use Illuminate\Support\Facades\Bus;

class CreateAnalysisAction
{
    public function execute(CreateAnalysisDTO $dto): Analysis
    {
        $analysis = Analysis::create([
            'uuid' => \Str::uuid(),
            'github_username' => $dto->username,
            'status' => 'pending',
            'ip_address' => $dto->ipAddress,
        ]);

        // Chain jobs sequentially
        Bus::chain([
            new FetchGitHubDataJob($analysis),
            new RunAIAnalysisJob($analysis),
            new SendAnalysisCompletedNotificationJob($analysis),
        ])->catch(function (\Throwable $e) use ($analysis) {
            $analysis->markAsFailed($e->getMessage());
        })->dispatch();

        return $analysis;
    }
}
```

### Using Job Batches

```php
<?php

use Illuminate\Bus\Batch;
use Illuminate\Support\Facades\Bus;

// For bulk analysis (admin feature)
Bus::batch([
    new ProcessAnalysisJob($analysis1),
    new ProcessAnalysisJob($analysis2),
    new ProcessAnalysisJob($analysis3),
])
->then(function (Batch $batch) {
    // All jobs completed
})
->catch(function (Batch $batch, \Throwable $e) {
    // First failure
})
->finally(function (Batch $batch) {
    // Batch finished (success or failure)
})
->name('bulk-analysis')
->dispatch();
```

---

## Horizon Setup

### Installation

```bash
composer require laravel/horizon
php artisan horizon:install
```

### Configuration

**config/horizon.php:**

```php
<?php

return [
    'domain' => env('HORIZON_DOMAIN'),
    'path' => 'horizon',
    'use' => 'default',
    'prefix' => env('HORIZON_PREFIX', 'horizon:'),
    'middleware' => ['web', 'auth'],

    'waits' => [
        'redis:default' => 60,
    ],

    'trim' => [
        'recent' => 60,
        'pending' => 60,
        'completed' => 60,
        'recent_failed' => 10080,
        'failed' => 10080,
        'monitored' => 10080,
    ],

    'silenced' => [],

    'metrics' => [
        'trim_snapshots' => [
            'job' => 24,
            'queue' => 24,
        ],
    ],

    'fast_termination' => false,
    'memory_limit' => 128,

    'defaults' => [
        'supervisor-1' => [
            'connection' => 'redis',
            'queue' => ['high', 'default', 'low'],
            'balance' => 'auto',
            'autoScalingStrategy' => 'time',
            'maxProcesses' => 10,
            'maxTime' => 0,
            'maxJobs' => 0,
            'memory' => 128,
            'tries' => 3,
            'timeout' => 180,
            'nice' => 0,
        ],
    ],

    'environments' => [
        'production' => [
            'supervisor-1' => [
                'maxProcesses' => 10,
                'balanceMaxShift' => 1,
                'balanceCooldown' => 3,
            ],
        ],

        'local' => [
            'supervisor-1' => [
                'maxProcesses' => 3,
            ],
        ],
    ],
];
```

### Horizon Service Provider

```php
<?php

namespace App\Providers;

use Illuminate\Support\Facades\Gate;
use Laravel\Horizon\Horizon;
use Laravel\Horizon\HorizonApplicationServiceProvider;

class HorizonServiceProvider extends HorizonApplicationServiceProvider
{
    public function boot(): void
    {
        parent::boot();

        Horizon::night();

        Horizon::auth(function ($request) {
            return Gate::check('viewHorizon', [$request->user()]);
        });
    }

    protected function gate(): void
    {
        Gate::define('viewHorizon', function ($user) {
            return $user->is_admin;
        });
    }
}
```

### Running Horizon

```bash
# Development
php artisan horizon

# Production (with supervisor)
php artisan horizon:supervisor
```

---

## Error Handling

### Custom Exception Handling for Jobs

```php
<?php

namespace App\Exceptions;

use Exception;

class AnalysisJobException extends Exception
{
    public function __construct(
        string $message,
        public readonly ?string $analysisId = null,
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }

    public function context(): array
    {
        return [
            'analysis_id' => $this->analysisId,
        ];
    }
}
```

### Retry Logic

```php
<?php

namespace App\Jobs;

class ProcessAnalysisJob implements ShouldQueue
{
    // Exponential backoff
    public function backoff(): array
    {
        return [30, 60, 120]; // 30s, 1m, 2m
    }

    // Or calculate dynamically
    public function backoff(): int
    {
        return $this->attempts() * 30;
    }

    // Retry until specific time
    public function retryUntil(): \DateTime
    {
        return now()->addMinutes(10);
    }

    // Determine if should retry
    public function shouldRetry(\Throwable $e): bool
    {
        // Don't retry for validation errors
        if ($e instanceof \Illuminate\Validation\ValidationException) {
            return false;
        }

        // Don't retry if user not found
        if ($e instanceof GitHubApiException && $e->getCode() === 404) {
            return false;
        }

        return true;
    }
}
```

### Failed Job Handling

```php
<?php

// In the job class
public function failed(\Throwable $exception): void
{
    // Log the failure
    Log::error('Job failed permanently', [
        'job' => static::class,
        'analysis_id' => $this->analysis->id,
        'exception' => $exception->getMessage(),
    ]);

    // Update analysis status
    $this->analysis->markAsFailed($exception->getMessage());

    // Send notification to admin
    Notification::route('mail', config('mail.admin'))
        ->notify(new JobFailedNotification($this->analysis, $exception));
}
```

---

## Monitoring

### Job Events

```php
<?php

namespace App\Providers;

use Illuminate\Queue\Events\JobFailed;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class QueueServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Event::listen(JobProcessing::class, function ($event) {
            Log::debug('Job starting', [
                'job' => $event->job->resolveName(),
            ]);
        });

        Event::listen(JobProcessed::class, function ($event) {
            Log::debug('Job completed', [
                'job' => $event->job->resolveName(),
            ]);
        });

        Event::listen(JobFailed::class, function ($event) {
            Log::error('Job failed', [
                'job' => $event->job->resolveName(),
                'exception' => $event->exception->getMessage(),
            ]);
        });
    }
}
```

### Scheduled Monitoring

**routes/console.php:**

```php
<?php

use App\Jobs\PruneOldAnalysesJob;
use Illuminate\Support\Facades\Schedule;

Schedule::job(new PruneOldAnalysesJob())->daily();

Schedule::command('queue:prune-failed --hours=168')->daily();

Schedule::command('horizon:snapshot')->everyFiveMinutes();
```

### Health Checks

```php
<?php

namespace App\Http\Controllers\Api;

use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Redis;

class HealthController extends Controller
{
    public function index()
    {
        return response()->json([
            'status' => 'healthy',
            'timestamp' => now()->toIso8601String(),
            'services' => [
                'database' => $this->checkDatabase(),
                'redis' => $this->checkRedis(),
                'queue' => $this->checkQueue(),
            ],
        ]);
    }

    private function checkDatabase(): string
    {
        try {
            \DB::connection()->getPdo();
            return 'connected';
        } catch (\Exception) {
            return 'disconnected';
        }
    }

    private function checkRedis(): string
    {
        try {
            Redis::ping();
            return 'connected';
        } catch (\Exception) {
            return 'disconnected';
        }
    }

    private function checkQueue(): string
    {
        try {
            $size = Queue::size();
            return "ready (pending: {$size})";
        } catch (\Exception) {
            return 'unavailable';
        }
    }
}
```

---

## Supervisor Configuration

**Production supervisor config:**

**/etc/supervisor/conf.d/gitroast-horizon.conf:**

```ini
[program:gitroast-horizon]
process_name=%(program_name)s
command=php /var/www/gitroast/artisan horizon
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
redirect_stderr=true
stdout_logfile=/var/www/gitroast/storage/logs/horizon.log
stopwaitsecs=3600
```

```bash
# Reload supervisor
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start gitroast-horizon
```

---

## Next Steps

1. Set up [Testing](09-TESTING.md)
2. Prepare [Deployment](10-DEPLOYMENT.md)
