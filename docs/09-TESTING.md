# Testing Documentation

Comprehensive testing guide for GitRoast using Pest PHP.

---

## Table of Contents

1. [Testing Setup](#testing-setup)
2. [Test Structure](#test-structure)
3. [Unit Tests](#unit-tests)
4. [Feature Tests](#feature-tests)
5. [Mocking External Services](#mocking-external-services)
6. [Database Testing](#database-testing)
7. [API Testing](#api-testing)
8. [Running Tests](#running-tests)

---

## Testing Setup

### Pest Installation

```bash
# Install Pest
composer require pestphp/pest --dev
composer require pestphp/pest-plugin-laravel --dev
composer require pestphp/pest-plugin-faker --dev

# Initialize
./vendor/bin/pest --init
```

### Configuration

**tests/Pest.php:**

```php
<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
*/

uses(Tests\TestCase::class, LazilyRefreshDatabase::class)->in('Feature');
uses(Tests\TestCase::class)->in('Unit');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
*/

expect()->extend('toBeValidUuid', function () {
    return $this->toMatch('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i');
});

expect()->extend('toBeValidScore', function () {
    return $this->toBeInt()->toBeBetween(0, 100);
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
*/

function createAnalysis(array $attributes = []): \App\Models\Analysis
{
    return \App\Models\Analysis::factory()->create($attributes);
}

function createPaidAnalysis(array $attributes = []): \App\Models\Analysis
{
    return \App\Models\Analysis::factory()->paid()->create($attributes);
}
```

**phpunit.xml:**

```xml
<?xml version="1.0" encoding="UTF-8"?>
<phpunit xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         xsi:noNamespaceSchemaLocation="vendor/phpunit/phpunit/phpunit.xsd"
         bootstrap="vendor/autoload.php"
         colors="true"
>
    <testsuites>
        <testsuite name="Unit">
            <directory>tests/Unit</directory>
        </testsuite>
        <testsuite name="Feature">
            <directory>tests/Feature</directory>
        </testsuite>
    </testsuites>
    <source>
        <include>
            <directory>app</directory>
        </include>
    </source>
    <php>
        <env name="APP_ENV" value="testing"/>
        <env name="BCRYPT_ROUNDS" value="4"/>
        <env name="CACHE_DRIVER" value="array"/>
        <env name="DB_CONNECTION" value="sqlite"/>
        <env name="DB_DATABASE" value=":memory:"/>
        <env name="MAIL_MAILER" value="array"/>
        <env name="QUEUE_CONNECTION" value="sync"/>
        <env name="SESSION_DRIVER" value="array"/>
        <env name="TELESCOPE_ENABLED" value="false"/>
    </php>
</phpunit>
```

---

## Test Structure

```
tests/
├── Feature/
│   ├── Api/
│   │   ├── AnalysisControllerTest.php
│   │   ├── CheckoutControllerTest.php
│   │   ├── HealthControllerTest.php
│   │   └── WebhookControllerTest.php
│   ├── Jobs/
│   │   └── ProcessAnalysisJobTest.php
│   └── Actions/
│       └── CreateAnalysisActionTest.php
├── Unit/
│   ├── DTOs/
│   │   └── AnalysisResultDTOTest.php
│   ├── Enums/
│   │   └── ScoreLevelTest.php
│   ├── Models/
│   │   ├── AnalysisTest.php
│   │   └── PaymentTest.php
│   └── Services/
│       ├── ScoreCalculatorServiceTest.php
│       └── GitHubServiceTest.php
├── Pest.php
└── TestCase.php
```

---

## Unit Tests

### Score Calculator Service Test

**tests/Unit/Services/ScoreCalculatorServiceTest.php:**

```php
<?php

use App\DTOs\Analysis\AnalysisResultDTO;
use App\Services\Analysis\ScoreCalculatorService;

beforeEach(function () {
    $this->calculator = new ScoreCalculatorService();
});

describe('calculateOverallScore', function () {
    it('calculates weighted score correctly', function () {
        $result = new AnalysisResultDTO(
            overallScore: 0, // Will be calculated
            summary: '',
            firstImpression: '',
            categories: [
                'profile_completeness' => ['score' => 80],      // 0.15 weight
                'project_quality' => ['score' => 70],           // 0.30 weight
                'contribution_consistency' => ['score' => 60],  // 0.20 weight
                'technical_signals' => ['score' => 90],         // 0.20 weight
                'community_engagement' => ['score' => 50],      // 0.15 weight
            ],
            dealBreakers: [],
            topProjectsAnalysis: [],
            improvementChecklist: [],
            strengths: [],
            recruiterPerspective: null,
        );

        $score = $this->calculator->calculateOverallScore($result);

        // Expected: 80*0.15 + 70*0.30 + 60*0.20 + 90*0.20 + 50*0.15 = 71.5 → 72
        expect($score)->toBe(72);
    });

    it('handles missing category scores', function () {
        $result = new AnalysisResultDTO(
            overallScore: 0,
            summary: '',
            firstImpression: '',
            categories: [
                'profile_completeness' => ['score' => 80],
                // Missing other categories
            ],
            dealBreakers: [],
            topProjectsAnalysis: [],
            improvementChecklist: [],
            strengths: [],
            recruiterPerspective: null,
        );

        $score = $this->calculator->calculateOverallScore($result);

        expect($score)->toBe(12); // 80 * 0.15 = 12
    });

    it('returns zero for empty categories', function () {
        $result = new AnalysisResultDTO(
            overallScore: 0,
            summary: '',
            firstImpression: '',
            categories: [],
            dealBreakers: [],
            topProjectsAnalysis: [],
            improvementChecklist: [],
            strengths: [],
            recruiterPerspective: null,
        );

        $score = $this->calculator->calculateOverallScore($result);

        expect($score)->toBe(0);
    });
});

describe('normalizeScore', function () {
    it('keeps valid scores unchanged', function () {
        expect($this->calculator->normalizeScore(50))->toBe(50);
        expect($this->calculator->normalizeScore(0))->toBe(0);
        expect($this->calculator->normalizeScore(100))->toBe(100);
    });

    it('clamps scores to valid range', function () {
        expect($this->calculator->normalizeScore(-10))->toBe(0);
        expect($this->calculator->normalizeScore(150))->toBe(100);
    });
});
```

### Score Level Enum Test

**tests/Unit/Enums/ScoreLevelTest.php:**

```php
<?php

use App\Enums\ScoreLevel;

describe('fromScore', function () {
    it('returns exceptional for scores 90-100', function () {
        expect(ScoreLevel::fromScore(90))->toBe(ScoreLevel::EXCEPTIONAL);
        expect(ScoreLevel::fromScore(95))->toBe(ScoreLevel::EXCEPTIONAL);
        expect(ScoreLevel::fromScore(100))->toBe(ScoreLevel::EXCEPTIONAL);
    });

    it('returns strong for scores 80-89', function () {
        expect(ScoreLevel::fromScore(80))->toBe(ScoreLevel::STRONG);
        expect(ScoreLevel::fromScore(85))->toBe(ScoreLevel::STRONG);
        expect(ScoreLevel::fromScore(89))->toBe(ScoreLevel::STRONG);
    });

    it('returns good for scores 70-79', function () {
        expect(ScoreLevel::fromScore(70))->toBe(ScoreLevel::GOOD);
        expect(ScoreLevel::fromScore(75))->toBe(ScoreLevel::GOOD);
    });

    it('returns average for scores 60-69', function () {
        expect(ScoreLevel::fromScore(60))->toBe(ScoreLevel::AVERAGE);
        expect(ScoreLevel::fromScore(65))->toBe(ScoreLevel::AVERAGE);
    });

    it('returns below_average for scores 50-59', function () {
        expect(ScoreLevel::fromScore(50))->toBe(ScoreLevel::BELOW_AVERAGE);
        expect(ScoreLevel::fromScore(55))->toBe(ScoreLevel::BELOW_AVERAGE);
    });

    it('returns poor for scores below 50', function () {
        expect(ScoreLevel::fromScore(49))->toBe(ScoreLevel::POOR);
        expect(ScoreLevel::fromScore(0))->toBe(ScoreLevel::POOR);
    });

    it('handles null scores', function () {
        expect(ScoreLevel::fromScore(null))->toBe(ScoreLevel::POOR);
    });
});

describe('label', function () {
    it('returns human readable labels', function () {
        expect(ScoreLevel::EXCEPTIONAL->label())->toBe('Exceptional');
        expect(ScoreLevel::STRONG->label())->toBe('Strong');
        expect(ScoreLevel::POOR->label())->toBe('Needs Work');
    });
});

describe('color', function () {
    it('returns valid hex colors', function () {
        expect(ScoreLevel::EXCEPTIONAL->color())->toMatch('/^#[0-9a-f]{6}$/i');
        expect(ScoreLevel::POOR->color())->toMatch('/^#[0-9a-f]{6}$/i');
    });
});
```

### Analysis Model Test

**tests/Unit/Models/AnalysisTest.php:**

```php
<?php

use App\Enums\AnalysisStatus;
use App\Enums\ScoreLevel;
use App\Models\Analysis;

describe('Analysis Model', function () {
    describe('casts', function () {
        it('casts status to enum', function () {
            $analysis = Analysis::factory()->create(['status' => 'completed']);

            expect($analysis->status)->toBeInstanceOf(AnalysisStatus::class);
            expect($analysis->status)->toBe(AnalysisStatus::COMPLETED);
        });

        it('casts github_data to array', function () {
            $analysis = Analysis::factory()->create([
                'github_data' => ['user' => ['name' => 'Test']],
            ]);

            expect($analysis->github_data)->toBeArray();
            expect($analysis->github_data['user']['name'])->toBe('Test');
        });

        it('casts is_paid to boolean', function () {
            $analysis = Analysis::factory()->create(['is_paid' => 1]);

            expect($analysis->is_paid)->toBeBool();
            expect($analysis->is_paid)->toBeTrue();
        });
    });

    describe('scopes', function () {
        it('filters by completed status', function () {
            Analysis::factory()->create(['status' => 'completed']);
            Analysis::factory()->create(['status' => 'pending']);
            Analysis::factory()->create(['status' => 'failed']);

            $completed = Analysis::completed()->get();

            expect($completed)->toHaveCount(1);
            expect($completed->first()->status)->toBe(AnalysisStatus::COMPLETED);
        });

        it('filters by paid status', function () {
            Analysis::factory()->create(['is_paid' => true]);
            Analysis::factory()->create(['is_paid' => false]);
            Analysis::factory()->create(['is_paid' => false]);

            expect(Analysis::paid()->count())->toBe(1);
            expect(Analysis::unpaid()->count())->toBe(2);
        });
    });

    describe('accessors', function () {
        it('returns correct score level', function () {
            $analysis = Analysis::factory()->create(['overall_score' => 85]);

            expect($analysis->score_level)->toBeInstanceOf(ScoreLevel::class);
            expect($analysis->score_level)->toBe(ScoreLevel::STRONG);
        });

        it('returns category scores array', function () {
            $analysis = Analysis::factory()->create([
                'profile_score' => 80,
                'projects_score' => 70,
                'consistency_score' => 60,
                'technical_score' => 90,
                'community_score' => 50,
            ]);

            expect($analysis->category_scores)->toBe([
                'profile' => 80,
                'projects' => 70,
                'consistency' => 60,
                'technical' => 90,
                'community' => 50,
            ]);
        });

        it('returns deal breakers from ai_analysis', function () {
            $dealBreakers = [
                ['issue' => 'No README', 'fix' => 'Add README'],
            ];

            $analysis = Analysis::factory()->create([
                'ai_analysis' => ['deal_breakers' => $dealBreakers],
            ]);

            expect($analysis->deal_breakers)->toBe($dealBreakers);
        });
    });

    describe('methods', function () {
        it('marks analysis as processing', function () {
            $analysis = Analysis::factory()->pending()->create();

            $analysis->markAsProcessing();

            expect($analysis->fresh()->status)->toBe(AnalysisStatus::PROCESSING);
        });

        it('marks analysis as completed', function () {
            $analysis = Analysis::factory()->processing()->create();

            $analysis->markAsCompleted([
                'overall_score' => 75,
                'ai_analysis' => ['summary' => 'Test'],
            ]);

            $fresh = $analysis->fresh();
            expect($fresh->status)->toBe(AnalysisStatus::COMPLETED);
            expect($fresh->overall_score)->toBe(75);
            expect($fresh->completed_at)->not->toBeNull();
        });

        it('marks analysis as failed', function () {
            $analysis = Analysis::factory()->processing()->create();

            $analysis->markAsFailed('API error');

            $fresh = $analysis->fresh();
            expect($fresh->status)->toBe(AnalysisStatus::FAILED);
            expect($fresh->error_message)->toBe('API error');
        });

        it('unlocks analysis after payment', function () {
            $analysis = Analysis::factory()->create(['is_paid' => false]);

            $analysis->unlock('pi_test123');

            $fresh = $analysis->fresh();
            expect($fresh->is_paid)->toBeTrue();
            expect($fresh->stripe_payment_id)->toBe('pi_test123');
            expect($fresh->paid_at)->not->toBeNull();
        });
    });
});
```

---

## Feature Tests

### Analysis Controller Test

**tests/Feature/Api/AnalysisControllerTest.php:**

```php
<?php

use App\Enums\AnalysisStatus;
use App\Jobs\ProcessAnalysisJob;
use App\Models\Analysis;
use Illuminate\Support\Facades\Queue;

describe('POST /api/analyze', function () {
    beforeEach(function () {
        Queue::fake();
    });

    it('creates a new analysis', function () {
        $response = $this->postJson('/api/analyze', [
            'username' => 'torvalds',
        ]);

        $response->assertStatus(202)
            ->assertJsonStructure([
                'data' => ['id', 'username', 'status', 'created_at'],
                'links' => ['self', 'status'],
            ]);

        expect(Analysis::count())->toBe(1);
        expect(Analysis::first()->github_username)->toBe('torvalds');
    });

    it('dispatches processing job', function () {
        $this->postJson('/api/analyze', ['username' => 'torvalds']);

        Queue::assertPushed(ProcessAnalysisJob::class, function ($job) {
            return $job->analysis->github_username === 'torvalds';
        });
    });

    it('validates username is required', function () {
        $response = $this->postJson('/api/analyze', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['username']);
    });

    it('validates username format', function () {
        $response = $this->postJson('/api/analyze', [
            'username' => 'invalid--username',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['username']);
    });

    it('rate limits requests', function () {
        for ($i = 0; $i < 10; $i++) {
            $this->postJson('/api/analyze', ['username' => "user{$i}"]);
        }

        $response = $this->postJson('/api/analyze', ['username' => 'user11']);

        $response->assertStatus(429);
    });
});

describe('GET /api/analysis/{uuid}', function () {
    it('returns analysis results', function () {
        $analysis = Analysis::factory()->create([
            'overall_score' => 75,
            'status' => AnalysisStatus::COMPLETED,
        ]);

        $response = $this->getJson("/api/analysis/{$analysis->uuid}");

        $response->assertStatus(200)
            ->assertJsonPath('data.overall_score', 75)
            ->assertJsonPath('data.status', 'completed');
    });

    it('returns 404 for invalid uuid', function () {
        $response = $this->getJson('/api/analysis/invalid-uuid');

        $response->assertStatus(404);
    });

    it('limits data for free tier', function () {
        $analysis = Analysis::factory()->create([
            'is_paid' => false,
            'ai_analysis' => [
                'deal_breakers' => [
                    ['issue' => '1'],
                    ['issue' => '2'],
                    ['issue' => '3'],
                    ['issue' => '4'],
                    ['issue' => '5'],
                ],
            ],
        ]);

        $response = $this->getJson("/api/analysis/{$analysis->uuid}");

        // Should only show 3 deal breakers for free tier
        expect(count($response->json('data.deal_breakers')))->toBe(3);
    });
});

describe('GET /api/analysis/{uuid}/status', function () {
    it('returns pending status', function () {
        $analysis = Analysis::factory()->pending()->create();

        $response = $this->getJson("/api/analysis/{$analysis->uuid}/status");

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'pending')
            ->assertJsonPath('data.progress', 10);
    });

    it('returns processing status with progress', function () {
        $analysis = Analysis::factory()->processing()->create();

        $response = $this->getJson("/api/analysis/{$analysis->uuid}/status");

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'processing')
            ->assertJsonPath('data.progress', 50);
    });

    it('returns completed with redirect', function () {
        $analysis = Analysis::factory()->create([
            'status' => AnalysisStatus::COMPLETED,
        ]);

        $response = $this->getJson("/api/analysis/{$analysis->uuid}/status");

        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'completed')
            ->assertJsonPath('data.progress', 100)
            ->assertJsonStructure(['data' => ['redirect']]);
    });
});

describe('GET /api/analysis/{uuid}/full', function () {
    it('returns 402 for unpaid analysis', function () {
        $analysis = Analysis::factory()->create(['is_paid' => false]);

        $response = $this->getJson("/api/analysis/{$analysis->uuid}/full");

        $response->assertStatus(402)
            ->assertJsonPath('message', 'Payment required for full report');
    });

    it('returns full report for paid analysis', function () {
        $analysis = Analysis::factory()->paid()->create([
            'ai_analysis' => [
                'summary' => 'Full summary',
                'categories' => [],
                'top_projects_analysis' => [],
            ],
        ]);

        $response = $this->getJson("/api/analysis/{$analysis->uuid}/full");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'summary',
                    'categories',
                    'top_projects_analysis',
                ],
            ]);
    });
});
```

### Process Analysis Job Test

**tests/Feature/Jobs/ProcessAnalysisJobTest.php:**

```php
<?php

use App\Enums\AnalysisStatus;
use App\Events\AnalysisCompleted;
use App\Events\AnalysisFailed;
use App\Jobs\ProcessAnalysisJob;
use App\Models\Analysis;
use App\Services\AI\AIAnalysisService;
use App\Services\GitHub\GitHubService;
use Illuminate\Support\Facades\Event;

beforeEach(function () {
    Event::fake();
});

it('processes analysis successfully', function () {
    // Mock GitHub service
    $this->mock(GitHubService::class, function ($mock) {
        $mock->shouldReceive('getProfileData')
            ->once()
            ->andReturn(mockGitHubProfile());
    });

    // Mock AI service
    $this->mock(AIAnalysisService::class, function ($mock) {
        $mock->shouldReceive('analyze')
            ->once()
            ->andReturn(mockAnalysisResult());
    });

    $analysis = Analysis::factory()->pending()->create();

    ProcessAnalysisJob::dispatch($analysis);

    $analysis->refresh();

    expect($analysis->status)->toBe(AnalysisStatus::COMPLETED);
    expect($analysis->overall_score)->toBeValidScore();
    expect($analysis->ai_analysis)->not->toBeNull();

    Event::assertDispatched(AnalysisCompleted::class);
});

it('handles github api errors', function () {
    $this->mock(GitHubService::class, function ($mock) {
        $mock->shouldReceive('getProfileData')
            ->andThrow(new \App\Exceptions\GitHubApiException('User not found', 404));
    });

    $analysis = Analysis::factory()->pending()->create();

    try {
        ProcessAnalysisJob::dispatchSync($analysis);
    } catch (\Exception) {
        // Expected to throw
    }

    $analysis->refresh();

    // After all retries, should be marked as failed
    expect($analysis->status)->toBe(AnalysisStatus::FAILED);
    expect($analysis->error_message)->toContain('User not found');
});

it('fires failed event on permanent failure', function () {
    $this->mock(GitHubService::class, function ($mock) {
        $mock->shouldReceive('getProfileData')
            ->andThrow(new \Exception('API Error'));
    });

    $analysis = Analysis::factory()->pending()->create();

    $job = new ProcessAnalysisJob($analysis);
    $job->failed(new \Exception('API Error'));

    Event::assertDispatched(AnalysisFailed::class);
});

// Helper functions
function mockGitHubProfile()
{
    return new \App\DTOs\Analysis\GitHubProfileDTO(
        username: 'testuser',
        name: 'Test User',
        bio: 'A developer',
        avatarUrl: 'https://example.com/avatar.jpg',
        location: 'USA',
        blog: 'https://blog.com',
        company: 'Test Co',
        twitterUsername: 'testuser',
        publicRepos: 10,
        followers: 100,
        following: 50,
        createdAt: '2020-01-01',
        profileReadme: '# Hello',
        repositories: collect([]),
        contributions: [],
    );
}

function mockAnalysisResult()
{
    return new \App\DTOs\Analysis\AnalysisResultDTO(
        overallScore: 75,
        summary: 'Good profile',
        firstImpression: 'Looks active',
        categories: [
            'profile_completeness' => ['score' => 80],
            'project_quality' => ['score' => 70],
            'contribution_consistency' => ['score' => 75],
            'technical_signals' => ['score' => 80],
            'community_engagement' => ['score' => 65],
        ],
        dealBreakers: [],
        topProjectsAnalysis: [],
        improvementChecklist: [],
        strengths: ['Active contributor'],
        recruiterPerspective: 'Would interview',
    );
}
```

---

## Mocking External Services

### Mocking GitHub API

```php
<?php

use App\Integrations\GitHub\GitHubConnector;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;

it('fetches user profile from github', function () {
    $mockClient = new MockClient([
        '*' => MockResponse::make([
            'login' => 'testuser',
            'name' => 'Test User',
            'bio' => 'Developer',
            'public_repos' => 10,
            'followers' => 100,
        ], 200),
    ]);

    $connector = new GitHubConnector();
    $connector->withMockClient($mockClient);

    $response = $connector->send(new GetUserRequest('testuser'));

    expect($response->json('login'))->toBe('testuser');

    $mockClient->assertSentCount(1);
});
```

### Mocking Stripe

```php
<?php

use Stripe\Checkout\Session;

it('creates checkout session', function () {
    // Use Stripe test mode or mock
    $this->mock(\App\Services\Payment\PaymentService::class, function ($mock) {
        $mock->shouldReceive('createCheckoutSession')
            ->once()
            ->andReturn(new \App\DTOs\Payment\CheckoutSessionDTO(
                sessionId: 'cs_test_123',
                checkoutUrl: 'https://checkout.stripe.com/test',
            ));
    });

    $analysis = Analysis::factory()->create();

    $response = $this->postJson('/api/checkout/create', [
        'analysis_id' => $analysis->uuid,
    ]);

    $response->assertStatus(200)
        ->assertJsonPath('data.session_id', 'cs_test_123');
});
```

---

## Running Tests

### Commands

```bash
# Run all tests
./vendor/bin/pest

# Run specific test file
./vendor/bin/pest tests/Feature/Api/AnalysisControllerTest.php

# Run specific test
./vendor/bin/pest --filter="creates a new analysis"

# Run with coverage
./vendor/bin/pest --coverage

# Run in parallel
./vendor/bin/pest --parallel

# Run only unit tests
./vendor/bin/pest --testsuite=Unit

# Run only feature tests
./vendor/bin/pest --testsuite=Feature

# Watch mode (requires fswatch)
./vendor/bin/pest --watch
```

### CI/CD Configuration

**.github/workflows/tests.yml:**

```yaml
name: Tests

on:
  push:
    branches: [main, develop]
  pull_request:
    branches: [main]

jobs:
  tests:
    runs-on: ubuntu-latest

    services:
      mysql:
        image: mysql:8.0
        env:
          MYSQL_ROOT_PASSWORD: password
          MYSQL_DATABASE: gitroast_test
        ports:
          - 3306:3306
        options: >-
          --health-cmd="mysqladmin ping"
          --health-interval=10s
          --health-timeout=5s
          --health-retries=3

      redis:
        image: redis:7
        ports:
          - 6379:6379

    steps:
      - uses: actions/checkout@v4

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.3'
          extensions: mbstring, pdo, mysql, redis
          coverage: xdebug

      - name: Install dependencies
        run: composer install --prefer-dist --no-progress

      - name: Copy .env
        run: cp .env.example .env

      - name: Generate key
        run: php artisan key:generate

      - name: Run migrations
        run: php artisan migrate --force
        env:
          DB_CONNECTION: mysql
          DB_HOST: 127.0.0.1
          DB_PORT: 3306
          DB_DATABASE: gitroast_test
          DB_USERNAME: root
          DB_PASSWORD: password

      - name: Run tests
        run: ./vendor/bin/pest --coverage --min=80
        env:
          DB_CONNECTION: mysql
          DB_HOST: 127.0.0.1
          DB_PORT: 3306
          DB_DATABASE: gitroast_test
          DB_USERNAME: root
          DB_PASSWORD: password
          REDIS_HOST: 127.0.0.1
```

---

## Next Steps

1. Prepare [Deployment](10-DEPLOYMENT.md)
