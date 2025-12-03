# GitRoast Implementation Documentation

This document describes the implemented features and how to use them.

## Overview

GitRoast is a GitHub profile analyzer that uses AI (Claude) to score and provide actionable feedback on GitHub profiles.

## Implemented Features

### 1. Database Structure

**Migrations created:**
- `analyses` - Stores analysis data, scores, and AI results
- `payments` - Tracks Paddle payment transactions
- `analysis_requests` - Rate limiting by IP address
- User table updated with `is_admin` flag for Filament access

### 2. Enums

Located in `app/Enums/`:
- `AnalysisStatus` - pending, processing, completed, failed
- `PaymentStatus` - pending, completed, failed, refunded
- `ScoreLevel` - exceptional, strong, good, average, below_average, poor
- `ScoreCategory` - Categories for scoring (profile, projects, consistency, technical, community)

### 3. Models

Located in `app/Models/`:
- `Analysis` - Main analysis model with scopes, accessors, and methods
- `Payment` - Payment tracking model with Paddle integration
- `AnalysisRequest` - Rate limiting model
- `User` - Updated with `is_admin` for Filament access

### 4. DTOs (Data Transfer Objects)

Located in `app/DTOs/`:
- `GitHubProfileDTO` - GitHub user profile data
- `GitHubRepoDTO` - Repository information
- `AnalysisResultDTO` - AI analysis results
- `CreateAnalysisDTO` - Analysis creation input
- `CheckoutSessionDTO` - Paddle checkout session

### 5. API Integrations (Saloon)

Located in `app/Integrations/`:

**GitHub:**
- `GitHubConnector` - Base connector for GitHub API
- `GetUserRequest` - Fetch user profile
- `GetUserReposRequest` - Fetch user repositories
- `GetRepoReadmeRequest` - Fetch repository README
- `GetUserEventsRequest` - Fetch user activity events

**Anthropic:**
- `AnthropicConnector` - Base connector for Claude API
- `AnalyzeProfileRequest` - Send profile for AI analysis

### 6. Services

Located in `app/Services/`:
- `GitHubService` - Fetch and process GitHub profile data
- `AIAnalysisService` - Send data to Claude for analysis
- `ScoreCalculatorService` - Calculate weighted scores
- `PaymentService` - Paddle checkout and webhook handling

### 7. Jobs

Located in `app/Jobs/`:
- `ProcessAnalysisJob` - Main analysis processing job (retries, error handling)
- `PruneOldAnalysesJob` - Cleanup old unpaid analyses

### 8. API Endpoints

Located in `app/Http/Controllers/Api/`:

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/analyze` | Create new analysis |
| GET | `/api/analysis/{uuid}` | Get analysis result |
| GET | `/api/analysis/{uuid}/status` | Get analysis status |
| GET | `/api/analysis/{uuid}/full` | Get full report (paid only) |
| POST | `/api/checkout/create` | Create Paddle checkout |
| GET | `/api/checkout/verify/{transactionId}` | Verify payment |
| POST | `/api/webhooks/paddle` | Handle Paddle webhooks |
| GET | `/api/health` | Health check endpoint |

### 9. Filament Admin Panel

Located in `app/Filament/`:

**Resources:**
- `AnalysisResource` - CRUD for analyses with filters, actions
- `PaymentResource` - View and manage payments

**Widgets:**
- `AnalysisStatsWidget` - Dashboard stats (total, completed, paid, avg score)
- `RecentAnalysesWidget` - Recent analyses table

**Access:**
- Navigate to `/admin`
- Requires user with `is_admin = true`

### 10. OpenAPI Documentation (Scramble)

API documentation is auto-generated and available at:
- `/docs/api` - Interactive Swagger UI

## Configuration

### Environment Variables

Add these to your `.env`:

```env
# GitHub API
GITHUB_TOKEN=your_github_token
GITHUB_API_VERSION=2022-11-28

# Anthropic (Claude AI)
ANTHROPIC_API_KEY=your_anthropic_key
ANTHROPIC_MODEL=claude-sonnet-4-20250514
ANTHROPIC_MAX_TOKENS=4096

# Paddle Payments
PADDLE_API_KEY=your_paddle_api_key
PADDLE_WEBHOOK_SECRET=your_webhook_secret
PADDLE_PRICE_FULL_REPORT=pri_xxx
PADDLE_SANDBOX=true

# Frontend URL (for redirects)
APP_FRONTEND_URL=http://localhost:3000
```

## Commands

```bash
# Run migrations
php artisan migrate

# Run queue worker
php artisan queue:work

# Run tests
./vendor/bin/pest

# Run code style fixer
./vendor/bin/pint

# Run static analysis
./vendor/bin/phpstan analyse

# Create admin user
php artisan tinker
>>> User::create(['name' => 'Admin', 'email' => 'admin@example.com', 'password' => Hash::make('password'), 'is_admin' => true])
```

## Testing

Tests are located in `tests/`:
- `tests/Unit/` - Unit tests for services, enums, models
- `tests/Feature/` - Feature tests for API endpoints

Run tests:
```bash
./vendor/bin/pest
```

## Code Quality

- **Pint**: Laravel code style fixer
- **Larastan**: PHPStan for Laravel (level 8)

See `phpstan.neon` for configuration.
