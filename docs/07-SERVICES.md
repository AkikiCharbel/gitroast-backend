# Services Documentation

Complete guide to the service layer architecture for GitRoast.

---

## Table of Contents

1. [Service Overview](#service-overview)
2. [GitHub Service](#github-service)
3. [AI Analysis Service](#ai-analysis-service)
4. [Payment Service](#payment-service)
5. [Score Calculator Service](#score-calculator-service)
6. [Integrations (Saloon)](#integrations-saloon)

---

## Service Overview

Services encapsulate business logic and external API interactions. They are injected via dependency injection and follow single responsibility principle.

```
app/Services/
├── Analysis/
│   ├── AnalysisService.php
│   ├── ScoreCalculatorService.php
│   └── ReportGeneratorService.php
├── GitHub/
│   └── GitHubService.php
├── AI/
│   └── AIAnalysisService.php
└── Payment/
    └── PaymentService.php

app/Integrations/
├── GitHub/
│   ├── GitHubConnector.php
│   └── Requests/
├── Anthropic/
│   ├── AnthropicConnector.php
│   └── Requests/
└── Stripe/
    ├── StripeConnector.php
    └── Requests/
```

---

## GitHub Service

### GitHubService

```php
<?php

namespace App\Services\GitHub;

use App\DTOs\Analysis\GitHubProfileDTO;
use App\DTOs\Analysis\GitHubRepoDTO;
use App\Exceptions\GitHubApiException;
use App\Integrations\GitHub\GitHubConnector;
use App\Integrations\GitHub\Requests\GetContributionsRequest;
use App\Integrations\GitHub\Requests\GetRepoReadmeRequest;
use App\Integrations\GitHub\Requests\GetUserReposRequest;
use App\Integrations\GitHub\Requests\GetUserRequest;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Saloon\Exceptions\Request\RequestException;

class GitHubService
{
    private const CACHE_TTL = 3600; // 1 hour
    
    public function __construct(
        private readonly GitHubConnector $connector
    ) {}
    
    /**
     * Get complete profile data for analysis.
     */
    public function getProfileData(string $username): GitHubProfileDTO
    {
        return Cache::remember(
            "github:profile:{$username}",
            self::CACHE_TTL,
            fn () => $this->fetchProfileData($username)
        );
    }
    
    /**
     * Fetch all profile data from GitHub API.
     */
    private function fetchProfileData(string $username): GitHubProfileDTO
    {
        try {
            // Fetch user profile
            $userResponse = $this->connector->send(new GetUserRequest($username));
            $userData = $userResponse->json();
            
            // Fetch repositories
            $reposResponse = $this->connector->send(
                new GetUserReposRequest($username, perPage: 30)
            );
            $reposData = $reposResponse->json();
            
            // Fetch profile README if exists
            $profileReadme = $this->getProfileReadme($username);
            
            // Fetch contribution data
            $contributions = $this->getContributionData($username);
            
            // Process repositories with READMEs
            $repositories = $this->processRepositories($username, $reposData);
            
            return new GitHubProfileDTO(
                username: $userData['login'],
                name: $userData['name'],
                bio: $userData['bio'],
                avatarUrl: $userData['avatar_url'],
                location: $userData['location'],
                blog: $userData['blog'],
                company: $userData['company'],
                twitterUsername: $userData['twitter_username'],
                publicRepos: $userData['public_repos'],
                followers: $userData['followers'],
                following: $userData['following'],
                createdAt: $userData['created_at'],
                profileReadme: $profileReadme,
                repositories: $repositories,
                contributions: $contributions,
            );
        } catch (RequestException $e) {
            if ($e->getResponse()?->status() === 404) {
                throw new GitHubApiException("GitHub user '{$username}' not found", 404);
            }
            
            if ($e->getResponse()?->status() === 403) {
                throw new GitHubApiException('GitHub API rate limit exceeded', 429);
            }
            
            throw new GitHubApiException(
                "Failed to fetch GitHub data: {$e->getMessage()}",
                $e->getCode()
            );
        }
    }
    
    /**
     * Get profile README content.
     */
    private function getProfileReadme(string $username): ?string
    {
        try {
            $response = $this->connector->send(
                new GetRepoReadmeRequest($username, $username)
            );
            
            $content = $response->json()['content'] ?? null;
            
            if ($content) {
                return base64_decode($content);
            }
        } catch (RequestException) {
            // Profile README doesn't exist
        }
        
        return null;
    }
    
    /**
     * Get contribution/activity data.
     */
    private function getContributionData(string $username): array
    {
        try {
            $response = $this->connector->send(
                new GetContributionsRequest($username)
            );
            
            return $response->json();
        } catch (RequestException) {
            return [];
        }
    }
    
    /**
     * Process repositories and fetch READMEs for top repos.
     */
    private function processRepositories(string $username, array $repos): Collection
    {
        // Sort by stars + recent activity
        $sorted = collect($repos)
            ->filter(fn ($repo) => !$repo['fork']) // Exclude forks
            ->sortByDesc(fn ($repo) => 
                $repo['stargazers_count'] * 2 + 
                (strtotime($repo['pushed_at']) > strtotime('-90 days') ? 10 : 0)
            )
            ->take(15);
        
        return $sorted->map(function ($repo) use ($username) {
            $readme = null;
            
            // Only fetch README for top 6 repos
            if ($sorted->search($repo) < 6) {
                $readme = $this->getRepoReadme($username, $repo['name']);
            }
            
            return new GitHubRepoDTO(
                name: $repo['name'],
                description: $repo['description'],
                language: $repo['language'],
                stargazersCount: $repo['stargazers_count'],
                forksCount: $repo['forks_count'],
                openIssuesCount: $repo['open_issues_count'],
                createdAt: $repo['created_at'],
                updatedAt: $repo['updated_at'],
                pushedAt: $repo['pushed_at'],
                topics: $repo['topics'] ?? [],
                license: $repo['license']['name'] ?? null,
                isFork: $repo['fork'],
                readme: $readme ? substr($readme, 0, 3000) : null,
            );
        });
    }
    
    /**
     * Get README content for a repository.
     */
    private function getRepoReadme(string $username, string $repo): ?string
    {
        try {
            $response = $this->connector->send(
                new GetRepoReadmeRequest($username, $repo)
            );
            
            $content = $response->json()['content'] ?? null;
            
            if ($content) {
                return base64_decode($content);
            }
        } catch (RequestException) {
            // README doesn't exist
        }
        
        return null;
    }
    
    /**
     * Check if a username exists.
     */
    public function userExists(string $username): bool
    {
        try {
            $this->connector->send(new GetUserRequest($username));
            return true;
        } catch (RequestException) {
            return false;
        }
    }
    
    /**
     * Get rate limit status.
     */
    public function getRateLimitStatus(): array
    {
        $response = $this->connector->send(
            new \Saloon\Http\Request() {
                protected Method $method = Method::GET;
                public function resolveEndpoint(): string
                {
                    return '/rate_limit';
                }
            }
        );
        
        return $response->json()['rate'] ?? [];
    }
}
```

### GitHub Connector (Saloon)

```php
<?php

namespace App\Integrations\GitHub;

use Saloon\Http\Connector;
use Saloon\Traits\Plugins\AcceptsJson;

class GitHubConnector extends Connector
{
    use AcceptsJson;
    
    public function resolveBaseUrl(): string
    {
        return 'https://api.github.com';
    }
    
    protected function defaultHeaders(): array
    {
        return [
            'Accept' => 'application/vnd.github+json',
            'Authorization' => 'Bearer ' . config('services.github.token'),
            'X-GitHub-Api-Version' => config('services.github.api_version', '2022-11-28'),
        ];
    }
    
    protected function defaultConfig(): array
    {
        return [
            'timeout' => 30,
        ];
    }
}
```

### GitHub Requests

```php
<?php

namespace App\Integrations\GitHub\Requests;

use Saloon\Enums\Method;
use Saloon\Http\Request;

class GetUserRequest extends Request
{
    protected Method $method = Method::GET;
    
    public function __construct(
        private readonly string $username
    ) {}
    
    public function resolveEndpoint(): string
    {
        return "/users/{$this->username}";
    }
}

class GetUserReposRequest extends Request
{
    protected Method $method = Method::GET;
    
    public function __construct(
        private readonly string $username,
        private readonly int $perPage = 30,
        private readonly string $sort = 'updated'
    ) {}
    
    public function resolveEndpoint(): string
    {
        return "/users/{$this->username}/repos";
    }
    
    protected function defaultQuery(): array
    {
        return [
            'per_page' => $this->perPage,
            'sort' => $this->sort,
            'direction' => 'desc',
        ];
    }
}

class GetRepoReadmeRequest extends Request
{
    protected Method $method = Method::GET;
    
    public function __construct(
        private readonly string $owner,
        private readonly string $repo
    ) {}
    
    public function resolveEndpoint(): string
    {
        return "/repos/{$this->owner}/{$this->repo}/readme";
    }
}

class GetContributionsRequest extends Request
{
    protected Method $method = Method::GET;
    
    public function __construct(
        private readonly string $username
    ) {}
    
    public function resolveEndpoint(): string
    {
        return "/users/{$this->username}/events/public";
    }
    
    protected function defaultQuery(): array
    {
        return [
            'per_page' => 100,
        ];
    }
}
```

---

## AI Analysis Service

### AIAnalysisService

```php
<?php

namespace App\Services\AI;

use App\DTOs\Analysis\AnalysisResultDTO;
use App\DTOs\Analysis\GitHubProfileDTO;
use App\Exceptions\AIAnalysisException;
use App\Integrations\Anthropic\AnthropicConnector;
use App\Integrations\Anthropic\Requests\AnalyzeProfileRequest;
use Illuminate\Support\Facades\Log;
use Saloon\Exceptions\Request\RequestException;

class AIAnalysisService
{
    public function __construct(
        private readonly AnthropicConnector $connector
    ) {}
    
    /**
     * Analyze a GitHub profile using Claude AI.
     */
    public function analyze(GitHubProfileDTO $profile): AnalysisResultDTO
    {
        $prompt = $this->buildPrompt($profile);
        
        try {
            $response = $this->connector->send(
                new AnalyzeProfileRequest($prompt)
            );
            
            $content = $response->json()['content'][0]['text'] ?? null;
            
            if (!$content) {
                throw new AIAnalysisException('Empty response from AI');
            }
            
            $analysis = $this->parseResponse($content);
            
            return AnalysisResultDTO::fromArray($analysis);
            
        } catch (RequestException $e) {
            Log::error('AI Analysis failed', [
                'username' => $profile->username,
                'error' => $e->getMessage(),
            ]);
            
            throw new AIAnalysisException(
                "AI analysis failed: {$e->getMessage()}",
                previous: $e
            );
        }
    }
    
    /**
     * Build the analysis prompt.
     */
    private function buildPrompt(GitHubProfileDTO $profile): string
    {
        $profileJson = json_encode([
            'username' => $profile->username,
            'name' => $profile->name,
            'bio' => $profile->bio,
            'location' => $profile->location,
            'blog' => $profile->blog,
            'company' => $profile->company,
            'twitter' => $profile->twitterUsername,
            'public_repos' => $profile->publicRepos,
            'followers' => $profile->followers,
            'following' => $profile->following,
            'account_age_days' => now()->diffInDays($profile->createdAt),
        ], JSON_PRETTY_PRINT);
        
        $reposJson = json_encode(
            $profile->repositories->map(fn ($repo) => [
                'name' => $repo->name,
                'description' => $repo->description,
                'language' => $repo->language,
                'stars' => $repo->stargazersCount,
                'forks' => $repo->forksCount,
                'topics' => $repo->topics,
                'has_readme' => !empty($repo->readme),
                'readme_length' => strlen($repo->readme ?? ''),
                'last_pushed' => $repo->pushedAt,
            ])->toArray(),
            JSON_PRETTY_PRINT
        );
        
        $readmeContent = $profile->profileReadme 
            ? substr($profile->profileReadme, 0, 2000) 
            : 'No profile README found';
        
        return <<<PROMPT
        Analyze this GitHub profile:

        Username: {$profile->username}

        Profile Data:
        {$profileJson}

        Repositories (top by stars and recent activity):
        {$reposJson}

        Profile README:
        {$readmeContent}

        Provide your analysis in the exact JSON format specified in the system prompt.
        PROMPT;
    }
    
    /**
     * Parse the AI response into structured data.
     */
    private function parseResponse(string $content): array
    {
        // Extract JSON from response (handle markdown code blocks)
        $content = preg_replace('/```json\s*/', '', $content);
        $content = preg_replace('/```\s*/', '', $content);
        $content = trim($content);
        
        $data = json_decode($content, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::warning('Failed to parse AI response as JSON', [
                'content' => substr($content, 0, 500),
                'error' => json_last_error_msg(),
            ]);
            
            throw new AIAnalysisException(
                'Failed to parse AI response: ' . json_last_error_msg()
            );
        }
        
        // Validate required fields
        $required = ['overall_score', 'categories', 'deal_breakers'];
        foreach ($required as $field) {
            if (!isset($data[$field])) {
                throw new AIAnalysisException("Missing required field: {$field}");
            }
        }
        
        return $data;
    }
    
    /**
     * Get the system prompt for analysis.
     */
    public function getSystemPrompt(): string
    {
        return file_get_contents(
            resource_path('prompts/github-analysis-system.txt')
        );
    }
}
```

### Anthropic Connector

```php
<?php

namespace App\Integrations\Anthropic;

use Saloon\Http\Connector;
use Saloon\Traits\Plugins\AcceptsJson;

class AnthropicConnector extends Connector
{
    use AcceptsJson;
    
    public function resolveBaseUrl(): string
    {
        return 'https://api.anthropic.com/v1';
    }
    
    protected function defaultHeaders(): array
    {
        return [
            'anthropic-version' => '2023-06-01',
            'x-api-key' => config('services.anthropic.api_key'),
        ];
    }
    
    protected function defaultConfig(): array
    {
        return [
            'timeout' => 120, // AI can take time
        ];
    }
}
```

### Anthropic Request

```php
<?php

namespace App\Integrations\Anthropic\Requests;

use Saloon\Contracts\Body\HasBody;
use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Traits\Body\HasJsonBody;

class AnalyzeProfileRequest extends Request implements HasBody
{
    use HasJsonBody;
    
    protected Method $method = Method::POST;
    
    public function __construct(
        private readonly string $userPrompt
    ) {}
    
    public function resolveEndpoint(): string
    {
        return '/messages';
    }
    
    protected function defaultBody(): array
    {
        return [
            'model' => config('services.anthropic.model', 'claude-sonnet-4-20250514'),
            'max_tokens' => config('services.anthropic.max_tokens', 4096),
            'system' => $this->getSystemPrompt(),
            'messages' => [
                [
                    'role' => 'user',
                    'content' => $this->userPrompt,
                ],
            ],
        ];
    }
    
    private function getSystemPrompt(): string
    {
        return <<<'PROMPT'
You are a senior technical recruiter and engineering hiring manager with 15 years of experience reviewing developer profiles at top tech companies.

Your task is to analyze a GitHub profile and provide brutally honest, actionable feedback.

Return ONLY valid JSON with this exact structure:

{
  "overall_score": <0-100>,
  "summary": "<2-3 sentence overall assessment>",
  "first_impression": "<What a recruiter thinks in the first 5 seconds>",
  "categories": {
    "profile_completeness": {
      "score": <0-100>,
      "issues": ["<issue 1>"],
      "recommendations": ["<specific fix>"],
      "details": "<paragraph explanation>"
    },
    "project_quality": { "score": <0-100>, "issues": [], "recommendations": [], "details": "" },
    "contribution_consistency": { "score": <0-100>, "issues": [], "recommendations": [], "details": "" },
    "technical_signals": { "score": <0-100>, "issues": [], "recommendations": [], "details": "" },
    "community_engagement": { "score": <0-100>, "issues": [], "recommendations": [], "details": "" }
  },
  "deal_breakers": [
    { "issue": "<critical issue>", "why_it_matters": "<why recruiters care>", "fix": "<how to fix>" }
  ],
  "top_projects_analysis": [
    {
      "repo_name": "<name>",
      "score": <0-100>,
      "strengths": ["<strength>"],
      "weaknesses": ["<weakness>"],
      "readme_quality": "<poor|basic|good|excellent>",
      "recommendations": ["<improvement>"]
    }
  ],
  "improvement_checklist": [
    { "priority": "<high|medium|low>", "task": "<action>", "time_estimate": "<e.g., 10 minutes>", "impact": "<result>" }
  ],
  "strengths": ["<genuine strength>"],
  "recruiter_perspective": "<What a recruiter would say in an internal meeting>"
}

SCORING: 90-100=Exceptional, 80-89=Strong, 70-79=Good, 60-69=Average, 50-59=Below average, <50=Poor

Be specific, honest, and actionable. Do not inflate scores.
PROMPT;
    }
}
```

---

## Payment Service

### PaymentService

```php
<?php

namespace App\Services\Payment;

use App\DTOs\Payment\CheckoutSessionDTO;
use App\Exceptions\PaymentException;
use App\Models\Analysis;
use App\Models\Payment;
use Stripe\Checkout\Session;
use Stripe\Exception\ApiErrorException;
use Stripe\Stripe;
use Stripe\Webhook;

class PaymentService
{
    public function __construct()
    {
        Stripe::setApiKey(config('services.stripe.secret'));
    }
    
    /**
     * Create a Stripe Checkout session.
     */
    public function createCheckoutSession(Analysis $analysis): CheckoutSessionDTO
    {
        if ($analysis->is_paid) {
            throw new PaymentException('Analysis is already paid');
        }
        
        try {
            $session = Session::create([
                'payment_method_types' => ['card'],
                'line_items' => [[
                    'price' => config('services.stripe.price_full_report'),
                    'quantity' => 1,
                ]],
                'mode' => 'payment',
                'success_url' => config('app.url') . "/success?session_id={CHECKOUT_SESSION_ID}",
                'cancel_url' => config('app.url') . "/analyze/{$analysis->uuid}",
                'metadata' => [
                    'analysis_id' => $analysis->id,
                    'analysis_uuid' => $analysis->uuid,
                    'github_username' => $analysis->github_username,
                ],
                'client_reference_id' => $analysis->uuid,
            ]);
            
            // Create payment record
            Payment::create([
                'analysis_id' => $analysis->id,
                'stripe_session_id' => $session->id,
                'amount_cents' => $session->amount_total,
                'currency' => strtoupper($session->currency),
                'status' => 'pending',
            ]);
            
            return new CheckoutSessionDTO(
                sessionId: $session->id,
                checkoutUrl: $session->url,
            );
            
        } catch (ApiErrorException $e) {
            throw new PaymentException(
                "Failed to create checkout session: {$e->getMessage()}",
                previous: $e
            );
        }
    }
    
    /**
     * Handle Stripe webhook event.
     */
    public function handleWebhook(string $payload, string $signature): void
    {
        try {
            $event = Webhook::constructEvent(
                $payload,
                $signature,
                config('services.stripe.webhook_secret')
            );
        } catch (\Exception $e) {
            throw new PaymentException("Webhook signature verification failed: {$e->getMessage()}");
        }
        
        match ($event->type) {
            'checkout.session.completed' => $this->handleCheckoutCompleted($event->data->object),
            'payment_intent.succeeded' => $this->handlePaymentSucceeded($event->data->object),
            'payment_intent.payment_failed' => $this->handlePaymentFailed($event->data->object),
            default => null,
        };
    }
    
    /**
     * Handle successful checkout.
     */
    private function handleCheckoutCompleted(Session $session): void
    {
        $payment = Payment::where('stripe_session_id', $session->id)->first();
        
        if (!$payment) {
            return;
        }
        
        $payment->update([
            'status' => 'completed',
            'stripe_payment_intent' => $session->payment_intent,
            'customer_email' => $session->customer_details?->email,
        ]);
        
        // Unlock the analysis
        $payment->analysis->unlock($session->payment_intent);
    }
    
    /**
     * Handle successful payment intent.
     */
    private function handlePaymentSucceeded($paymentIntent): void
    {
        $payment = Payment::where('stripe_payment_intent', $paymentIntent->id)->first();
        
        if ($payment && $payment->status !== 'completed') {
            $payment->markAsCompleted($paymentIntent->id);
        }
    }
    
    /**
     * Handle failed payment.
     */
    private function handlePaymentFailed($paymentIntent): void
    {
        $payment = Payment::where('stripe_payment_intent', $paymentIntent->id)->first();
        
        if ($payment) {
            $payment->markAsFailed();
        }
    }
    
    /**
     * Verify a payment was successful.
     */
    public function verifyPayment(string $sessionId): bool
    {
        try {
            $session = Session::retrieve($sessionId);
            return $session->payment_status === 'paid';
        } catch (ApiErrorException) {
            return false;
        }
    }
}
```

---

## Score Calculator Service

### ScoreCalculatorService

```php
<?php

namespace App\Services\Analysis;

use App\DTOs\Analysis\AnalysisResultDTO;
use App\Enums\ScoreCategory;

class ScoreCalculatorService
{
    /**
     * Category weights for overall score.
     */
    private const WEIGHTS = [
        'profile_completeness' => 0.15,
        'project_quality' => 0.30,
        'contribution_consistency' => 0.20,
        'technical_signals' => 0.20,
        'community_engagement' => 0.15,
    ];
    
    /**
     * Calculate overall score from category scores.
     */
    public function calculateOverallScore(AnalysisResultDTO $result): int
    {
        $weightedSum = 0;
        
        foreach (self::WEIGHTS as $category => $weight) {
            $score = $result->categories[$category]['score'] ?? 0;
            $weightedSum += $score * $weight;
        }
        
        return (int) round($weightedSum);
    }
    
    /**
     * Extract category scores from analysis result.
     */
    public function extractCategoryScores(AnalysisResultDTO $result): array
    {
        return [
            'profile' => $result->categories['profile_completeness']['score'] ?? 0,
            'projects' => $result->categories['project_quality']['score'] ?? 0,
            'consistency' => $result->categories['contribution_consistency']['score'] ?? 0,
            'technical' => $result->categories['technical_signals']['score'] ?? 0,
            'community' => $result->categories['community_engagement']['score'] ?? 0,
        ];
    }
    
    /**
     * Validate and normalize score.
     */
    public function normalizeScore(int $score): int
    {
        return max(0, min(100, $score));
    }
    
    /**
     * Calculate score trend from historical data.
     */
    public function calculateTrend(array $historicalScores): string
    {
        if (count($historicalScores) < 2) {
            return 'stable';
        }
        
        $recent = array_slice($historicalScores, -3);
        $average = array_sum($recent) / count($recent);
        $oldest = $historicalScores[0];
        
        $change = $average - $oldest;
        
        return match (true) {
            $change > 5 => 'improving',
            $change < -5 => 'declining',
            default => 'stable',
        };
    }
}
```

---

## DTOs

### GitHubProfileDTO

```php
<?php

namespace App\DTOs\Analysis;

use Illuminate\Support\Collection;
use Spatie\LaravelData\Data;

class GitHubProfileDTO extends Data
{
    public function __construct(
        public readonly string $username,
        public readonly ?string $name,
        public readonly ?string $bio,
        public readonly ?string $avatarUrl,
        public readonly ?string $location,
        public readonly ?string $blog,
        public readonly ?string $company,
        public readonly ?string $twitterUsername,
        public readonly int $publicRepos,
        public readonly int $followers,
        public readonly int $following,
        public readonly string $createdAt,
        public readonly ?string $profileReadme,
        public readonly Collection $repositories,
        public readonly array $contributions,
    ) {}
}
```

### AnalysisResultDTO

```php
<?php

namespace App\DTOs\Analysis;

use Spatie\LaravelData\Data;

class AnalysisResultDTO extends Data
{
    public function __construct(
        public readonly int $overallScore,
        public readonly string $summary,
        public readonly string $firstImpression,
        public readonly array $categories,
        public readonly array $dealBreakers,
        public readonly array $topProjectsAnalysis,
        public readonly array $improvementChecklist,
        public readonly array $strengths,
        public readonly ?string $recruiterPerspective,
    ) {}
    
    public static function fromArray(array $data): self
    {
        return new self(
            overallScore: $data['overall_score'],
            summary: $data['summary'] ?? '',
            firstImpression: $data['first_impression'] ?? '',
            categories: $data['categories'] ?? [],
            dealBreakers: $data['deal_breakers'] ?? [],
            topProjectsAnalysis: $data['top_projects_analysis'] ?? [],
            improvementChecklist: $data['improvement_checklist'] ?? [],
            strengths: $data['strengths'] ?? [],
            recruiterPerspective: $data['recruiter_perspective'] ?? null,
        );
    }
}
```

---

## Service Provider Registration

### IntegrationServiceProvider

```php
<?php

namespace App\Providers;

use App\Integrations\Anthropic\AnthropicConnector;
use App\Integrations\GitHub\GitHubConnector;
use App\Services\AI\AIAnalysisService;
use App\Services\Analysis\ScoreCalculatorService;
use App\Services\GitHub\GitHubService;
use App\Services\Payment\PaymentService;
use Illuminate\Support\ServiceProvider;

class IntegrationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // GitHub
        $this->app->singleton(GitHubConnector::class);
        $this->app->singleton(GitHubService::class, function ($app) {
            return new GitHubService($app->make(GitHubConnector::class));
        });
        
        // Anthropic
        $this->app->singleton(AnthropicConnector::class);
        $this->app->singleton(AIAnalysisService::class, function ($app) {
            return new AIAnalysisService($app->make(AnthropicConnector::class));
        });
        
        // Payment
        $this->app->singleton(PaymentService::class);
        
        // Analysis
        $this->app->singleton(ScoreCalculatorService::class);
    }
}
```

---

## Next Steps

1. Configure [Queue Jobs](08-QUEUES.md)
2. Set up [Testing](09-TESTING.md)
3. Prepare [Deployment](10-DEPLOYMENT.md)
