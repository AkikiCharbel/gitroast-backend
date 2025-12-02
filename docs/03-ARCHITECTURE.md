# Architecture Documentation

Comprehensive guide to the GitRoast application architecture, folder structure, and design patterns.

---

## Table of Contents

1. [Architectural Overview](#architectural-overview)
2. [Design Principles](#design-principles)
3. [Folder Structure](#folder-structure)
4. [Layer Responsibilities](#layer-responsibilities)
5. [Design Patterns](#design-patterns)
6. [Data Flow](#data-flow)
7. [Dependency Injection](#dependency-injection)

---

## Architectural Overview

GitRoast follows a **Modular Monolith** architecture with **Action-based** design pattern, combining the simplicity of a monolith with the organization of microservices.

```
┌─────────────────────────────────────────────────────────────────┐
│                       PRESENTATION LAYER                        │
├─────────────────────────────────────────────────────────────────┤
│  API Controllers  │  Filament Resources  │  API Resources       │
├─────────────────────────────────────────────────────────────────┤
│                       APPLICATION LAYER                         │
├─────────────────────────────────────────────────────────────────┤
│  Actions  │  Form Requests  │  DTOs  │  Events  │  Listeners    │
├─────────────────────────────────────────────────────────────────┤
│                        DOMAIN LAYER                             │
├─────────────────────────────────────────────────────────────────┤
│  Services  │  Models  │  Enums  │  Value Objects  │  Policies   │
├─────────────────────────────────────────────────────────────────┤
│                     INFRASTRUCTURE LAYER                        │
├─────────────────────────────────────────────────────────────────┤
│  Integrations (GitHub, Claude, Stripe)  │  Jobs  │  Repository  │
└─────────────────────────────────────────────────────────────────┘
```

---

## Design Principles

### SOLID Principles Applied

| Principle | Application |
|-----------|-------------|
| **Single Responsibility** | Each Action class does one thing |
| **Open/Closed** | Services are extendable via interfaces |
| **Liskov Substitution** | Connectors implement contracts |
| **Interface Segregation** | Small, focused interfaces |
| **Dependency Inversion** | Services depend on abstractions |

### Key Decisions

1. **Actions over Controllers** - Business logic lives in Action classes, not controllers
2. **DTOs for Data Transfer** - Type-safe data objects between layers
3. **Services for External APIs** - Dedicated services for GitHub, Claude, Stripe
4. **Events for Side Effects** - Decouple main flow from notifications, logging
5. **Jobs for Async Work** - Heavy processing happens in background queues

---

## Folder Structure

```
app/
├── Actions/                          # Single-purpose action classes
│   ├── Analysis/
│   │   ├── CreateAnalysisAction.php
│   │   ├── ProcessAnalysisAction.php
│   │   ├── UnlockAnalysisAction.php
│   │   └── GetAnalysisResultsAction.php
│   └── Payment/
│       ├── CreateCheckoutSessionAction.php
│       └── HandleStripeWebhookAction.php
│
├── Console/
│   └── Commands/
│       ├── PruneOldAnalysesCommand.php
│       └── RetryFailedAnalysesCommand.php
│
├── Contracts/                        # Interfaces/Contracts
│   ├── AIAnalyzerInterface.php
│   ├── GitHubClientInterface.php
│   └── PaymentGatewayInterface.php
│
├── DTOs/                             # Data Transfer Objects
│   ├── Analysis/
│   │   ├── AnalysisResultDTO.php
│   │   ├── CategoryScoreDTO.php
│   │   ├── DealBreakerDTO.php
│   │   ├── GitHubProfileDTO.php
│   │   ├── ImprovementItemDTO.php
│   │   └── ProjectAnalysisDTO.php
│   └── Payment/
│       └── CheckoutSessionDTO.php
│
├── Enums/
│   ├── AnalysisStatus.php
│   ├── PaymentStatus.php
│   ├── ScoreCategory.php
│   └── ScoreLevel.php
│
├── Events/
│   ├── AnalysisCompleted.php
│   ├── AnalysisFailed.php
│   ├── AnalysisStarted.php
│   └── PaymentReceived.php
│
├── Exceptions/
│   ├── AnalysisException.php
│   ├── GitHubApiException.php
│   ├── InsufficientCreditsException.php
│   └── RateLimitExceededException.php
│
├── Filament/                         # Admin Panel
│   ├── Resources/
│   │   ├── AnalysisResource/
│   │   │   ├── Pages/
│   │   │   │   ├── CreateAnalysis.php
│   │   │   │   ├── EditAnalysis.php
│   │   │   │   ├── ListAnalyses.php
│   │   │   │   └── ViewAnalysis.php
│   │   │   └── AnalysisResource.php
│   │   ├── PaymentResource.php
│   │   └── UserResource.php
│   ├── Widgets/
│   │   ├── AnalysisStatsWidget.php
│   │   ├── ConversionRateWidget.php
│   │   ├── RecentAnalysesWidget.php
│   │   └── RevenueChartWidget.php
│   └── Pages/
│       └── Dashboard.php
│
├── Http/
│   ├── Controllers/
│   │   └── Api/
│   │       ├── AnalysisController.php
│   │       ├── CheckoutController.php
│   │       ├── HealthController.php
│   │       └── WebhookController.php
│   ├── Middleware/
│   │   ├── EnsureAnalysisOwner.php
│   │   ├── RateLimitAnalysis.php
│   │   └── VerifyStripeWebhook.php
│   ├── Requests/
│   │   ├── CreateAnalysisRequest.php
│   │   └── CreateCheckoutRequest.php
│   └── Resources/
│       ├── AnalysisResource.php
│       ├── AnalysisFreeResource.php
│       ├── AnalysisFullResource.php
│       └── PaymentResource.php
│
├── Integrations/                     # External API Integrations
│   ├── GitHub/
│   │   ├── GitHubConnector.php
│   │   ├── Requests/
│   │   │   ├── GetUserRequest.php
│   │   │   ├── GetUserReposRequest.php
│   │   │   ├── GetRepoReadmeRequest.php
│   │   │   └── GetContributionsRequest.php
│   │   └── Responses/
│   │       ├── GitHubUserResponse.php
│   │       └── GitHubRepoResponse.php
│   ├── Anthropic/
│   │   ├── AnthropicConnector.php
│   │   ├── Requests/
│   │   │   └── AnalyzeProfileRequest.php
│   │   └── Responses/
│   │       └── AnalysisResponse.php
│   └── Stripe/
│       ├── StripeConnector.php
│       └── Requests/
│           └── CreateCheckoutSessionRequest.php
│
├── Jobs/
│   ├── ProcessAnalysisJob.php
│   ├── FetchGitHubDataJob.php
│   ├── RunAIAnalysisJob.php
│   └── SendAnalysisCompletedNotificationJob.php
│
├── Listeners/
│   ├── LogAnalysisActivity.php
│   ├── NotifyAnalysisCompleted.php
│   └── UpdateAnalysisStats.php
│
├── Models/
│   ├── Analysis.php
│   ├── AnalysisRequest.php
│   ├── Payment.php
│   └── User.php
│
├── Observers/
│   ├── AnalysisObserver.php
│   └── PaymentObserver.php
│
├── OpenApi/                          # OpenAPI Schema Definitions
│   ├── Schemas/
│   │   ├── AnalysisSchema.php
│   │   ├── ErrorSchema.php
│   │   └── PaymentSchema.php
│   └── OpenApiSpec.php
│
├── Policies/
│   ├── AnalysisPolicy.php
│   └── PaymentPolicy.php
│
├── Providers/
│   ├── AppServiceProvider.php
│   ├── EventServiceProvider.php
│   ├── FilamentServiceProvider.php
│   └── IntegrationServiceProvider.php
│
├── Rules/
│   ├── ValidGitHubUsername.php
│   └── NotRateLimited.php
│
└── Services/                         # Business Logic Services
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
```

---

## Layer Responsibilities

### Presentation Layer

**API Controllers** (`app/Http/Controllers/Api/`)
- Handle HTTP requests/responses
- Validate input via Form Requests
- Delegate to Actions
- Return API Resources

```php
<?php

namespace App\Http\Controllers\Api;

use App\Actions\Analysis\CreateAnalysisAction;
use App\Http\Requests\CreateAnalysisRequest;
use App\Http\Resources\AnalysisResource;

class AnalysisController extends Controller
{
    public function store(
        CreateAnalysisRequest $request,
        CreateAnalysisAction $action
    ): AnalysisResource {
        $analysis = $action->execute($request->toDTO());
        
        return new AnalysisResource($analysis);
    }
}
```

**API Resources** (`app/Http/Resources/`)
- Transform models into JSON
- Control data exposure (free vs paid)
- Format dates, nested relationships

```php
<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class AnalysisFreeResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->uuid,
            'username' => $this->github_username,
            'status' => $this->status,
            'overall_score' => $this->overall_score,
            'category_scores' => [
                'profile' => $this->profile_score,
                'projects' => $this->projects_score,
                'consistency' => $this->consistency_score,
                'technical' => $this->technical_score,
                'community' => $this->community_score,
            ],
            'deal_breakers' => collect($this->ai_analysis['deal_breakers'] ?? [])
                ->take(3)
                ->toArray(),
            'is_paid' => $this->is_paid,
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}
```

### Application Layer

**Actions** (`app/Actions/`)
- Single-purpose classes
- Coordinate services
- Handle transactions
- Fire events

```php
<?php

namespace App\Actions\Analysis;

use App\DTOs\Analysis\CreateAnalysisDTO;
use App\Events\AnalysisStarted;
use App\Jobs\ProcessAnalysisJob;
use App\Models\Analysis;
use Illuminate\Support\Str;

class CreateAnalysisAction
{
    public function execute(CreateAnalysisDTO $dto): Analysis
    {
        $analysis = Analysis::create([
            'uuid' => Str::uuid(),
            'github_username' => $dto->username,
            'status' => AnalysisStatus::PENDING,
            'ip_address' => $dto->ipAddress,
            'user_agent' => $dto->userAgent,
        ]);
        
        ProcessAnalysisJob::dispatch($analysis);
        
        event(new AnalysisStarted($analysis));
        
        return $analysis;
    }
}
```

**DTOs** (`app/DTOs/`)
- Type-safe data containers
- Immutable where possible
- Validation in Form Requests, not DTOs

```php
<?php

namespace App\DTOs\Analysis;

use Spatie\LaravelData\Data;

class CreateAnalysisDTO extends Data
{
    public function __construct(
        public readonly string $username,
        public readonly ?string $ipAddress = null,
        public readonly ?string $userAgent = null,
    ) {}
}
```

**Form Requests** (`app/Http/Requests/`)
- Input validation
- Authorization checks
- DTO conversion

```php
<?php

namespace App\Http\Requests;

use App\DTOs\Analysis\CreateAnalysisDTO;
use App\Rules\ValidGitHubUsername;
use App\Rules\NotRateLimited;
use Illuminate\Foundation\Http\FormRequest;

class CreateAnalysisRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'username' => [
                'required',
                'string',
                'max:39',
                new ValidGitHubUsername(),
                new NotRateLimited(),
            ],
        ];
    }
    
    public function toDTO(): CreateAnalysisDTO
    {
        return new CreateAnalysisDTO(
            username: $this->validated('username'),
            ipAddress: $this->ip(),
            userAgent: $this->userAgent(),
        );
    }
}
```

### Domain Layer

**Services** (`app/Services/`)
- Core business logic
- Orchestrate integrations
- Calculate scores
- Generate reports

```php
<?php

namespace App\Services\Analysis;

use App\DTOs\Analysis\AnalysisResultDTO;
use App\Enums\ScoreCategory;

class ScoreCalculatorService
{
    private const WEIGHTS = [
        ScoreCategory::PROFILE->value => 0.15,
        ScoreCategory::PROJECTS->value => 0.30,
        ScoreCategory::CONSISTENCY->value => 0.20,
        ScoreCategory::TECHNICAL->value => 0.20,
        ScoreCategory::COMMUNITY->value => 0.15,
    ];
    
    public function calculateOverall(AnalysisResultDTO $result): int
    {
        $weightedSum = 0;
        
        foreach (self::WEIGHTS as $category => $weight) {
            $score = $result->categoryScores[$category] ?? 0;
            $weightedSum += $score * $weight;
        }
        
        return (int) round($weightedSum);
    }
}
```

**Models** (`app/Models/`)
- Eloquent models
- Relationships
- Scopes
- Accessors/Mutators

```php
<?php

namespace App\Models;

use App\Enums\AnalysisStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Analysis extends Model
{
    protected $fillable = [
        'uuid',
        'github_username',
        'status',
        'overall_score',
        'profile_score',
        'projects_score',
        'consistency_score',
        'technical_score',
        'community_score',
        'github_data',
        'ai_analysis',
        'is_paid',
        'stripe_payment_id',
        'paid_at',
        'ip_address',
        'user_agent',
    ];
    
    protected $casts = [
        'status' => AnalysisStatus::class,
        'github_data' => 'array',
        'ai_analysis' => 'array',
        'is_paid' => 'boolean',
        'paid_at' => 'datetime',
    ];
    
    // Relationships
    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class);
    }
    
    // Scopes
    public function scopeCompleted($query)
    {
        return $query->where('status', AnalysisStatus::COMPLETED);
    }
    
    public function scopePaid($query)
    {
        return $query->where('is_paid', true);
    }
    
    // Accessors
    public function getScoreLevelAttribute(): string
    {
        return match(true) {
            $this->overall_score >= 90 => 'exceptional',
            $this->overall_score >= 80 => 'strong',
            $this->overall_score >= 70 => 'good',
            $this->overall_score >= 60 => 'average',
            $this->overall_score >= 50 => 'below_average',
            default => 'poor',
        };
    }
}
```

### Infrastructure Layer

**Integrations** (`app/Integrations/`)
- External API clients (Saloon)
- Request/Response handling
- Rate limiting
- Caching

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
            'X-GitHub-Api-Version' => config('services.github.api_version'),
        ];
    }
}
```

**Jobs** (`app/Jobs/`)
- Background processing
- Retry logic
- Error handling

```php
<?php

namespace App\Jobs;

use App\Models\Analysis;
use App\Services\AI\AIAnalysisService;
use App\Services\GitHub\GitHubService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessAnalysisJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    
    public int $tries = 3;
    public array $backoff = [30, 60, 120];
    
    public function __construct(
        public Analysis $analysis
    ) {}
    
    public function handle(
        GitHubService $github,
        AIAnalysisService $ai
    ): void {
        // Implementation
    }
}
```

---

## Design Patterns

### 1. Action Pattern

Single-purpose classes for business operations:

```php
// Instead of fat controllers or services
$analysis = app(CreateAnalysisAction::class)->execute($dto);
```

### 2. DTO Pattern

Type-safe data transfer between layers:

```php
// From request to action
$dto = $request->toDTO();
$analysis = $action->execute($dto);
```

### 3. Repository Pattern (Optional)

For complex queries:

```php
// app/Repositories/AnalysisRepository.php
class AnalysisRepository
{
    public function findRecentByUsername(string $username): ?Analysis
    {
        return Analysis::query()
            ->where('github_username', $username)
            ->where('created_at', '>', now()->subHours(24))
            ->latest()
            ->first();
    }
}
```

### 4. Strategy Pattern

For swappable implementations:

```php
// Contract
interface AIAnalyzerInterface
{
    public function analyze(GitHubProfileDTO $profile): AnalysisResultDTO;
}

// Claude implementation
class ClaudeAnalyzer implements AIAnalyzerInterface { ... }

// OpenAI fallback
class OpenAIAnalyzer implements AIAnalyzerInterface { ... }
```

### 5. Observer Pattern

For model events:

```php
// app/Observers/AnalysisObserver.php
class AnalysisObserver
{
    public function created(Analysis $analysis): void
    {
        activity()
            ->performedOn($analysis)
            ->log('Analysis created');
    }
}
```

---

## Data Flow

### Analysis Request Flow

```
1. User Request
   └── POST /api/analyze { username: "torvalds" }

2. Middleware
   └── RateLimitAnalysis → Check IP limits

3. Form Request
   └── CreateAnalysisRequest → Validate + Create DTO

4. Controller
   └── AnalysisController → Delegate to Action

5. Action
   └── CreateAnalysisAction
       ├── Check cache for recent analysis
       ├── Create Analysis model (pending)
       ├── Dispatch ProcessAnalysisJob
       └── Fire AnalysisStarted event

6. Queue Job
   └── ProcessAnalysisJob
       ├── Update status → processing
       ├── GitHubService → Fetch profile data
       ├── AIAnalysisService → Run Claude analysis
       ├── ScoreCalculatorService → Calculate scores
       ├── Update Analysis model (completed)
       └── Fire AnalysisCompleted event

7. Response
   └── AnalysisResource → JSON response with UUID

8. Polling
   └── GET /api/analysis/{uuid}/status
       └── Return current status + results when complete
```

---

## Dependency Injection

### Service Provider Bindings

```php
<?php

namespace App\Providers;

use App\Contracts\AIAnalyzerInterface;
use App\Contracts\GitHubClientInterface;
use App\Integrations\Anthropic\ClaudeAnalyzer;
use App\Services\GitHub\GitHubService;
use Illuminate\Support\ServiceProvider;

class IntegrationServiceProvider extends ServiceProvider
{
    public array $bindings = [
        GitHubClientInterface::class => GitHubService::class,
        AIAnalyzerInterface::class => ClaudeAnalyzer::class,
    ];
    
    public function register(): void
    {
        $this->app->singleton(GitHubService::class, function ($app) {
            return new GitHubService(
                connector: new GitHubConnector(),
                cache: $app['cache.store'],
            );
        });
    }
}
```

### Constructor Injection

```php
class ProcessAnalysisJob implements ShouldQueue
{
    public function handle(
        GitHubService $github,        // Auto-injected
        AIAnalysisService $ai,        // Auto-injected
        ScoreCalculatorService $calc  // Auto-injected
    ): void {
        // Services available via DI
    }
}
```

---

## Next Steps

1. Continue to [Database Documentation](04-DATABASE.md) for schema details
2. Review [API Documentation](05-API.md) for endpoints
3. Set up [Filament Admin](06-FILAMENT.md)
