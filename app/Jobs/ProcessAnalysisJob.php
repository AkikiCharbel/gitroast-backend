<?php

declare(strict_types=1);

namespace App\Jobs;

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
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * Number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * Backoff intervals in seconds.
     *
     * @var array<int, int>
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
            $this->analysis->markAsProcessing();

            $profileData = $github->getProfileData($this->analysis->github_username);

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
                    'has_profile_readme' => ! empty($profileData->profileReadme),
                    'top_repos_count' => $profileData->repositories->count(),
                ],
            ]);

            $analysisResult = $ai->analyze($profileData);

            $categoryScores = $calculator->extractCategoryScores($analysisResult);
            $overallScore = $calculator->calculateOverallScore($analysisResult);

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

    private function handleFailure(\Throwable $e, string $context): void
    {
        Log::error("Analysis failed: {$context}", [
            'id' => $this->analysis->id,
            'username' => $this->analysis->github_username,
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
        ]);

        if ($this->attempts() >= $this->tries) {
            $this->analysis->markAsFailed("{$context}: {$e->getMessage()}");
        }

        throw $e;
    }

    public function failed(\Throwable $exception): void
    {
        $this->analysis->markAsFailed($exception->getMessage());

        Log::critical('Analysis job permanently failed', [
            'id' => $this->analysis->id,
            'username' => $this->analysis->github_username,
            'error' => $exception->getMessage(),
        ]);
    }

    public function retryUntil(): \DateTime
    {
        return now()->addMinutes(10)->toDateTime();
    }

    public function uniqueId(): string
    {
        return $this->analysis->uuid;
    }

    /**
     * @return array<int, string>
     */
    public function tags(): array
    {
        return [
            'analysis',
            'username:'.$this->analysis->github_username,
        ];
    }
}
