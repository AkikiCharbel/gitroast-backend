# API Documentation

Complete REST API documentation with OpenAPI 3.0 schema definitions.

---

## Table of Contents

1. [API Overview](#api-overview)
2. [Authentication](#authentication)
3. [Endpoints](#endpoints)
4. [OpenAPI Schema Setup](#openapi-schema-setup)
5. [Request/Response Examples](#requestresponse-examples)
6. [Error Handling](#error-handling)
7. [Rate Limiting](#rate-limiting)

---

## API Overview

### Base URL

```
Production: https://gitroast.dev/api
Development: http://localhost:8000/api
```

### API Documentation UI

```
Swagger UI: /api/documentation
OpenAPI JSON: /api/docs
```

### Response Format

All responses follow JSON:API-inspired format:

```json
{
    "data": { ... },
    "meta": {
        "timestamp": "2024-01-15T10:30:00Z"
    }
}
```

### Error Response Format

```json
{
    "message": "Human readable error message",
    "errors": {
        "field": ["Validation error 1", "Validation error 2"]
    }
}
```

---

## Authentication

### Public Endpoints (No Auth Required)

- `POST /api/analyze` - Create analysis
- `GET /api/analysis/{uuid}` - Get analysis results
- `GET /api/analysis/{uuid}/status` - Check analysis status
- `POST /api/checkout/create` - Create payment session
- `GET /api/health` - Health check

### Webhook Endpoints (Signature Verification)

- `POST /api/webhook/stripe` - Stripe webhooks (verified via signature)

---

## Endpoints

### Health Check

```
GET /api/health
```

**Response:**
```json
{
    "status": "healthy",
    "timestamp": "2024-01-15T10:30:00Z",
    "services": {
        "database": "connected",
        "redis": "connected",
        "github": "available",
        "ai": "available"
    }
}
```

---

### Create Analysis

```
POST /api/analyze
```

**Request Body:**
```json
{
    "username": "torvalds"
}
```

**Response (202 Accepted):**
```json
{
    "data": {
        "id": "550e8400-e29b-41d4-a716-446655440000",
        "username": "torvalds",
        "status": "pending",
        "created_at": "2024-01-15T10:30:00Z"
    },
    "links": {
        "self": "/api/analysis/550e8400-e29b-41d4-a716-446655440000",
        "status": "/api/analysis/550e8400-e29b-41d4-a716-446655440000/status"
    }
}
```

---

### Get Analysis Status

```
GET /api/analysis/{uuid}/status
```

**Response (Processing):**
```json
{
    "data": {
        "id": "550e8400-e29b-41d4-a716-446655440000",
        "status": "processing",
        "progress": 50
    }
}
```

**Response (Completed):**
```json
{
    "data": {
        "id": "550e8400-e29b-41d4-a716-446655440000",
        "status": "completed",
        "redirect": "/api/analysis/550e8400-e29b-41d4-a716-446655440000"
    }
}
```

---

### Get Analysis Results (Free Tier)

```
GET /api/analysis/{uuid}
```

**Response:**
```json
{
    "data": {
        "id": "550e8400-e29b-41d4-a716-446655440000",
        "username": "torvalds",
        "status": "completed",
        "overall_score": 73,
        "score_level": "good",
        "score_label": "Good",
        "category_scores": {
            "profile": 85,
            "projects": 72,
            "consistency": 68,
            "technical": 78,
            "community": 65
        },
        "first_impression": "A recruiter would see: experienced developer with...",
        "deal_breakers": [
            {
                "issue": "No profile README",
                "why_it_matters": "Recruiters look for this first",
                "fix": "Create a username/username repository with README.md"
            },
            {
                "issue": "Top projects have no descriptions",
                "why_it_matters": "Impossible to understand at a glance",
                "fix": "Add clear descriptions to your top 5 repositories"
            },
            {
                "issue": "45-day gap in contributions",
                "why_it_matters": "Raises questions about consistency",
                "fix": "Maintain regular commit activity"
            }
        ],
        "is_paid": false,
        "created_at": "2024-01-15T10:30:00Z"
    },
    "meta": {
        "free_tier": true,
        "full_report_available": true
    },
    "links": {
        "checkout": "/api/checkout/create"
    }
}
```

---

### Get Full Analysis (Paid)

```
GET /api/analysis/{uuid}/full
```

**Response (If Paid):**
```json
{
    "data": {
        "id": "550e8400-e29b-41d4-a716-446655440000",
        "username": "torvalds",
        "overall_score": 73,
        "score_level": "good",
        "summary": "Your profile shows an experienced developer but...",
        "first_impression": "A recruiter would see...",
        "category_scores": {
            "profile": 85,
            "projects": 72,
            "consistency": 68,
            "technical": 78,
            "community": 65
        },
        "categories": {
            "profile": {
                "score": 85,
                "issues": ["No custom status"],
                "recommendations": ["Add a status message"],
                "details": "Your profile is mostly complete..."
            }
        },
        "deal_breakers": [ ... ],
        "top_projects_analysis": [
            {
                "repo_name": "linux",
                "score": 92,
                "strengths": ["Well documented", "Active development"],
                "weaknesses": ["Complex for newcomers"],
                "readme_quality": "excellent",
                "recommendations": ["Add contributing guide"]
            }
        ],
        "improvement_checklist": [
            {
                "priority": "high",
                "task": "Create profile README",
                "time_estimate": "30 minutes",
                "impact": "Significantly improves first impression"
            }
        ],
        "strengths": ["Consistent contributor", "Clean code"],
        "recruiter_perspective": "In an internal meeting, a recruiter would say...",
        "is_paid": true,
        "created_at": "2024-01-15T10:30:00Z"
    }
}
```

**Response (If Not Paid - 402):**
```json
{
    "message": "Payment required for full report",
    "links": {
        "checkout": "/api/checkout/create"
    }
}
```

---

### Create Checkout Session

```
POST /api/checkout/create
```

**Request Body:**
```json
{
    "analysis_id": "550e8400-e29b-41d4-a716-446655440000"
}
```

**Response:**
```json
{
    "data": {
        "checkout_url": "https://checkout.stripe.com/c/pay/cs_xxx",
        "session_id": "cs_xxx"
    }
}
```

---

### Stripe Webhook

```
POST /api/webhook/stripe
```

**Headers Required:**
```
Stripe-Signature: t=xxx,v1=xxx
```

Handled events:
- `checkout.session.completed`
- `payment_intent.succeeded`
- `payment_intent.payment_failed`

---

## OpenAPI Schema Setup

### Main OpenAPI Specification File

Create `app/OpenApi/OpenApiSpec.php`:

```php
<?php

namespace App\OpenApi;

/**
 * @OA\Info(
 *     version="1.0.0",
 *     title="GitRoast API",
 *     description="AI-powered GitHub Profile Analyzer API",
 *     @OA\Contact(
 *         email="support@gitroast.dev",
 *         name="GitRoast Support"
 *     ),
 *     @OA\License(
 *         name="Proprietary",
 *         url="https://gitroast.dev/terms"
 *     )
 * )
 *
 * @OA\Server(
 *     url=L5_SWAGGER_CONST_HOST,
 *     description="API Server"
 * )
 *
 * @OA\Tag(
 *     name="Analysis",
 *     description="GitHub profile analysis endpoints"
 * )
 *
 * @OA\Tag(
 *     name="Payment",
 *     description="Payment and checkout endpoints"
 * )
 *
 * @OA\Tag(
 *     name="Health",
 *     description="System health endpoints"
 * )
 */
class OpenApiSpec
{
}
```

### Schema Definitions

Create `app/OpenApi/Schemas/AnalysisSchema.php`:

```php
<?php

namespace App\OpenApi\Schemas;

/**
 * @OA\Schema(
 *     schema="Analysis",
 *     type="object",
 *     required={"id", "username", "status"},
 *     @OA\Property(
 *         property="id",
 *         type="string",
 *         format="uuid",
 *         example="550e8400-e29b-41d4-a716-446655440000"
 *     ),
 *     @OA\Property(
 *         property="username",
 *         type="string",
 *         example="torvalds"
 *     ),
 *     @OA\Property(
 *         property="status",
 *         type="string",
 *         enum={"pending", "processing", "completed", "failed"},
 *         example="completed"
 *     ),
 *     @OA\Property(
 *         property="overall_score",
 *         type="integer",
 *         minimum=0,
 *         maximum=100,
 *         example=73
 *     ),
 *     @OA\Property(
 *         property="score_level",
 *         type="string",
 *         enum={"exceptional", "strong", "good", "average", "below_average", "poor"},
 *         example="good"
 *     ),
 *     @OA\Property(
 *         property="score_label",
 *         type="string",
 *         example="Good"
 *     ),
 *     @OA\Property(
 *         property="category_scores",
 *         ref="#/components/schemas/CategoryScores"
 *     ),
 *     @OA\Property(
 *         property="first_impression",
 *         type="string",
 *         example="A recruiter would see: experienced developer..."
 *     ),
 *     @OA\Property(
 *         property="deal_breakers",
 *         type="array",
 *         @OA\Items(ref="#/components/schemas/DealBreaker")
 *     ),
 *     @OA\Property(
 *         property="is_paid",
 *         type="boolean",
 *         example=false
 *     ),
 *     @OA\Property(
 *         property="created_at",
 *         type="string",
 *         format="date-time",
 *         example="2024-01-15T10:30:00Z"
 *     )
 * )
 *
 * @OA\Schema(
 *     schema="CategoryScores",
 *     type="object",
 *     @OA\Property(property="profile", type="integer", example=85),
 *     @OA\Property(property="projects", type="integer", example=72),
 *     @OA\Property(property="consistency", type="integer", example=68),
 *     @OA\Property(property="technical", type="integer", example=78),
 *     @OA\Property(property="community", type="integer", example=65)
 * )
 *
 * @OA\Schema(
 *     schema="DealBreaker",
 *     type="object",
 *     @OA\Property(
 *         property="issue",
 *         type="string",
 *         example="No profile README"
 *     ),
 *     @OA\Property(
 *         property="why_it_matters",
 *         type="string",
 *         example="Recruiters look for this first"
 *     ),
 *     @OA\Property(
 *         property="fix",
 *         type="string",
 *         example="Create a username/username repository"
 *     )
 * )
 *
 * @OA\Schema(
 *     schema="FullAnalysis",
 *     allOf={
 *         @OA\Schema(ref="#/components/schemas/Analysis"),
 *         @OA\Schema(
 *             @OA\Property(
 *                 property="summary",
 *                 type="string"
 *             ),
 *             @OA\Property(
 *                 property="categories",
 *                 type="object",
 *                 additionalProperties={
 *                     "type": "object",
 *                     "$ref": "#/components/schemas/CategoryDetail"
 *                 }
 *             ),
 *             @OA\Property(
 *                 property="top_projects_analysis",
 *                 type="array",
 *                 @OA\Items(ref="#/components/schemas/ProjectAnalysis")
 *             ),
 *             @OA\Property(
 *                 property="improvement_checklist",
 *                 type="array",
 *                 @OA\Items(ref="#/components/schemas/ImprovementItem")
 *             ),
 *             @OA\Property(
 *                 property="strengths",
 *                 type="array",
 *                 @OA\Items(type="string")
 *             ),
 *             @OA\Property(
 *                 property="recruiter_perspective",
 *                 type="string"
 *             )
 *         )
 *     }
 * )
 *
 * @OA\Schema(
 *     schema="CategoryDetail",
 *     type="object",
 *     @OA\Property(property="score", type="integer"),
 *     @OA\Property(property="issues", type="array", @OA\Items(type="string")),
 *     @OA\Property(property="recommendations", type="array", @OA\Items(type="string")),
 *     @OA\Property(property="details", type="string")
 * )
 *
 * @OA\Schema(
 *     schema="ProjectAnalysis",
 *     type="object",
 *     @OA\Property(property="repo_name", type="string"),
 *     @OA\Property(property="score", type="integer"),
 *     @OA\Property(property="strengths", type="array", @OA\Items(type="string")),
 *     @OA\Property(property="weaknesses", type="array", @OA\Items(type="string")),
 *     @OA\Property(
 *         property="readme_quality",
 *         type="string",
 *         enum={"poor", "basic", "good", "excellent"}
 *     ),
 *     @OA\Property(property="recommendations", type="array", @OA\Items(type="string"))
 * )
 *
 * @OA\Schema(
 *     schema="ImprovementItem",
 *     type="object",
 *     @OA\Property(
 *         property="priority",
 *         type="string",
 *         enum={"high", "medium", "low"}
 *     ),
 *     @OA\Property(property="task", type="string"),
 *     @OA\Property(property="time_estimate", type="string"),
 *     @OA\Property(property="impact", type="string")
 * )
 */
class AnalysisSchema
{
}
```

### Request Schemas

Create `app/OpenApi/Schemas/RequestSchemas.php`:

```php
<?php

namespace App\OpenApi\Schemas;

/**
 * @OA\Schema(
 *     schema="CreateAnalysisRequest",
 *     type="object",
 *     required={"username"},
 *     @OA\Property(
 *         property="username",
 *         type="string",
 *         minLength=1,
 *         maxLength=39,
 *         pattern="^[a-zA-Z0-9](?:[a-zA-Z0-9]|-(?=[a-zA-Z0-9])){0,38}$",
 *         example="torvalds",
 *         description="GitHub username to analyze"
 *     )
 * )
 *
 * @OA\Schema(
 *     schema="CreateCheckoutRequest",
 *     type="object",
 *     required={"analysis_id"},
 *     @OA\Property(
 *         property="analysis_id",
 *         type="string",
 *         format="uuid",
 *         example="550e8400-e29b-41d4-a716-446655440000",
 *         description="UUID of the analysis to unlock"
 *     )
 * )
 */
class RequestSchemas
{
}
```

### Response Schemas

Create `app/OpenApi/Schemas/ResponseSchemas.php`:

```php
<?php

namespace App\OpenApi\Schemas;

/**
 * @OA\Schema(
 *     schema="AnalysisResponse",
 *     type="object",
 *     @OA\Property(
 *         property="data",
 *         ref="#/components/schemas/Analysis"
 *     ),
 *     @OA\Property(
 *         property="meta",
 *         type="object",
 *         @OA\Property(property="free_tier", type="boolean"),
 *         @OA\Property(property="full_report_available", type="boolean")
 *     ),
 *     @OA\Property(
 *         property="links",
 *         type="object",
 *         @OA\Property(property="self", type="string"),
 *         @OA\Property(property="checkout", type="string")
 *     )
 * )
 *
 * @OA\Schema(
 *     schema="CheckoutResponse",
 *     type="object",
 *     @OA\Property(
 *         property="data",
 *         type="object",
 *         @OA\Property(
 *             property="checkout_url",
 *             type="string",
 *             format="uri",
 *             example="https://checkout.stripe.com/c/pay/cs_xxx"
 *         ),
 *         @OA\Property(
 *             property="session_id",
 *             type="string",
 *             example="cs_xxx"
 *         )
 *     )
 * )
 *
 * @OA\Schema(
 *     schema="StatusResponse",
 *     type="object",
 *     @OA\Property(
 *         property="data",
 *         type="object",
 *         @OA\Property(property="id", type="string", format="uuid"),
 *         @OA\Property(
 *             property="status",
 *             type="string",
 *             enum={"pending", "processing", "completed", "failed"}
 *         ),
 *         @OA\Property(property="progress", type="integer"),
 *         @OA\Property(property="redirect", type="string")
 *     )
 * )
 *
 * @OA\Schema(
 *     schema="HealthResponse",
 *     type="object",
 *     @OA\Property(property="status", type="string", example="healthy"),
 *     @OA\Property(property="timestamp", type="string", format="date-time"),
 *     @OA\Property(
 *         property="services",
 *         type="object",
 *         @OA\Property(property="database", type="string"),
 *         @OA\Property(property="redis", type="string"),
 *         @OA\Property(property="github", type="string"),
 *         @OA\Property(property="ai", type="string")
 *     )
 * )
 *
 * @OA\Schema(
 *     schema="ErrorResponse",
 *     type="object",
 *     @OA\Property(
 *         property="message",
 *         type="string",
 *         example="The given data was invalid."
 *     ),
 *     @OA\Property(
 *         property="errors",
 *         type="object",
 *         additionalProperties={
 *             "type": "array",
 *             "items": {"type": "string"}
 *         }
 *     )
 * )
 */
class ResponseSchemas
{
}
```

### Controller Annotations

Create `app/Http/Controllers/Api/AnalysisController.php`:

```php
<?php

namespace App\Http\Controllers\Api;

use App\Actions\Analysis\CreateAnalysisAction;
use App\Actions\Analysis\GetAnalysisResultsAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\CreateAnalysisRequest;
use App\Http\Resources\AnalysisFreeResource;
use App\Http\Resources\AnalysisFullResource;
use App\Models\Analysis;
use Illuminate\Http\JsonResponse;
use OpenApi\Annotations as OA;

class AnalysisController extends Controller
{
    /**
     * @OA\Post(
     *     path="/analyze",
     *     summary="Create a new GitHub profile analysis",
     *     description="Starts an asynchronous analysis of the specified GitHub profile",
     *     operationId="createAnalysis",
     *     tags={"Analysis"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/CreateAnalysisRequest")
     *     ),
     *     @OA\Response(
     *         response=202,
     *         description="Analysis queued successfully",
     *         @OA\JsonContent(
     *             @OA\Property(
     *                 property="data",
     *                 type="object",
     *                 @OA\Property(property="id", type="string", format="uuid"),
     *                 @OA\Property(property="username", type="string"),
     *                 @OA\Property(property="status", type="string"),
     *                 @OA\Property(property="created_at", type="string", format="date-time")
     *             ),
     *             @OA\Property(
     *                 property="links",
     *                 type="object",
     *                 @OA\Property(property="self", type="string"),
     *                 @OA\Property(property="status", type="string")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     ),
     *     @OA\Response(
     *         response=429,
     *         description="Rate limit exceeded",
     *         @OA\JsonContent(ref="#/components/schemas/ErrorResponse")
     *     )
     * )
     */
    public function store(
        CreateAnalysisRequest $request,
        CreateAnalysisAction $action
    ): JsonResponse {
        $analysis = $action->execute($request->toDTO());
        
        return response()->json([
            'data' => [
                'id' => $analysis->uuid,
                'username' => $analysis->github_username,
                'status' => $analysis->status->value,
                'created_at' => $analysis->created_at->toIso8601String(),
            ],
            'links' => [
                'self' => route('api.analysis.show', $analysis),
                'status' => route('api.analysis.status', $analysis),
            ],
        ], 202);
    }

    /**
     * @OA\Get(
     *     path="/analysis/{uuid}",
     *     summary="Get analysis results (free tier)",
     *     description="Returns the analysis results. Free tier shows limited data.",
     *     operationId="getAnalysis",
     *     tags={"Analysis"},
     *     @OA\Parameter(
     *         name="uuid",
     *         in="path",
     *         required=true,
     *         description="Analysis UUID",
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *         @OA\JsonContent(ref="#/components/schemas/AnalysisResponse")
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Analysis not found"
     *     )
     * )
     */
    public function show(Analysis $analysis): AnalysisFreeResource
    {
        return new AnalysisFreeResource($analysis);
    }

    /**
     * @OA\Get(
     *     path="/analysis/{uuid}/status",
     *     summary="Check analysis status",
     *     description="Returns the current status of an analysis",
     *     operationId="getAnalysisStatus",
     *     tags={"Analysis"},
     *     @OA\Parameter(
     *         name="uuid",
     *         in="path",
     *         required=true,
     *         description="Analysis UUID",
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Status response",
     *         @OA\JsonContent(ref="#/components/schemas/StatusResponse")
     *     )
     * )
     */
    public function status(Analysis $analysis): JsonResponse
    {
        return response()->json([
            'data' => [
                'id' => $analysis->uuid,
                'status' => $analysis->status->value,
                'progress' => $this->calculateProgress($analysis),
                'redirect' => $analysis->is_complete 
                    ? route('api.analysis.show', $analysis) 
                    : null,
            ],
        ]);
    }

    /**
     * @OA\Get(
     *     path="/analysis/{uuid}/full",
     *     summary="Get full analysis (paid)",
     *     description="Returns the complete analysis. Requires payment.",
     *     operationId="getFullAnalysis",
     *     tags={"Analysis"},
     *     @OA\Parameter(
     *         name="uuid",
     *         in="path",
     *         required=true,
     *         description="Analysis UUID",
     *         @OA\Schema(type="string", format="uuid")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Full analysis response",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", ref="#/components/schemas/FullAnalysis")
     *         )
     *     ),
     *     @OA\Response(
     *         response=402,
     *         description="Payment required",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string"),
     *             @OA\Property(
     *                 property="links",
     *                 type="object",
     *                 @OA\Property(property="checkout", type="string")
     *             )
     *         )
     *     )
     * )
     */
    public function full(Analysis $analysis): JsonResponse|AnalysisFullResource
    {
        if (!$analysis->is_paid) {
            return response()->json([
                'message' => 'Payment required for full report',
                'links' => [
                    'checkout' => route('api.checkout.create'),
                ],
            ], 402);
        }
        
        return new AnalysisFullResource($analysis);
    }
    
    private function calculateProgress(Analysis $analysis): int
    {
        return match($analysis->status->value) {
            'pending' => 10,
            'processing' => 50,
            'completed' => 100,
            'failed' => 0,
            default => 0,
        };
    }
}
```

---

## Routes Configuration

Create `routes/api.php`:

```php
<?php

use App\Http\Controllers\Api\AnalysisController;
use App\Http\Controllers\Api\CheckoutController;
use App\Http\Controllers\Api\HealthController;
use App\Http\Controllers\Api\WebhookController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// Health Check
Route::get('/health', [HealthController::class, 'index'])
    ->name('api.health');

// Analysis Routes
Route::prefix('analysis')->group(function () {
    Route::post('/analyze', [AnalysisController::class, 'store'])
        ->middleware(['throttle:analysis'])
        ->name('api.analysis.store');
    
    Route::get('/{analysis}', [AnalysisController::class, 'show'])
        ->name('api.analysis.show');
    
    Route::get('/{analysis}/status', [AnalysisController::class, 'status'])
        ->name('api.analysis.status');
    
    Route::get('/{analysis}/full', [AnalysisController::class, 'full'])
        ->name('api.analysis.full');
});

// Checkout Routes
Route::prefix('checkout')->group(function () {
    Route::post('/create', [CheckoutController::class, 'create'])
        ->name('api.checkout.create');
});

// Webhook Routes (no CSRF)
Route::prefix('webhook')->group(function () {
    Route::post('/stripe', [WebhookController::class, 'stripe'])
        ->name('api.webhook.stripe');
});
```

---

## Error Handling

### Custom Exception Handler

Update `bootstrap/app.php`:

```php
<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Illuminate\Validation\ValidationException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        api: __DIR__.'/../routes/api.php',
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->api(prepend: [
            \App\Http\Middleware\ForceJsonResponse::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $exceptions->renderable(function (NotFoundHttpException $e, $request) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Resource not found',
                ], 404);
            }
        });
    })
    ->create();
```

### Standard Error Responses

| Code | Meaning | When Used |
|------|---------|-----------|
| 400 | Bad Request | Malformed request |
| 402 | Payment Required | Accessing paid features |
| 404 | Not Found | Resource doesn't exist |
| 422 | Validation Error | Input validation failed |
| 429 | Too Many Requests | Rate limit exceeded |
| 500 | Server Error | Internal error |
| 503 | Service Unavailable | External service down |

---

## Rate Limiting

### Configuration

In `bootstrap/app.php`:

```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->throttleWithRedis();
})
```

In `app/Providers/AppServiceProvider.php`:

```php
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;

public function boot(): void
{
    RateLimiter::for('analysis', function ($request) {
        return [
            // 10 analyses per IP per hour
            Limit::perHour(10)->by($request->ip()),
            // 5 analyses per username per hour
            Limit::perHour(5)->by($request->input('username')),
        ];
    });
}
```

### Rate Limit Headers

All responses include:
- `X-RateLimit-Limit`: Maximum requests allowed
- `X-RateLimit-Remaining`: Requests remaining
- `X-RateLimit-Reset`: Unix timestamp when limit resets

---

## Generating Documentation

```bash
# Generate OpenAPI JSON
php artisan l5-swagger:generate

# Access documentation
# Browser: http://localhost:8000/api/documentation

# Download OpenAPI spec
# http://localhost:8000/api/docs
```

---

## Next Steps

1. Set up [Filament Admin Panel](06-FILAMENT.md)
2. Implement [Services](07-SERVICES.md)
3. Configure [Queue Jobs](08-QUEUES.md)
