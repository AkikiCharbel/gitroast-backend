# Database Documentation

Complete database schema, migrations, models, and relationships for GitRoast.

---

## Table of Contents

1. [Schema Overview](#schema-overview)
2. [Entity Relationship Diagram](#entity-relationship-diagram)
3. [Migrations](#migrations)
4. [Models](#models)
5. [Enums](#enums)
6. [Factories & Seeders](#factories--seeders)
7. [Indexes & Performance](#indexes--performance)

---

## Schema Overview

### Tables Summary

| Table | Purpose | Records Expected |
|-------|---------|------------------|
| `users` | Admin users (Filament) | ~10 |
| `analyses` | Profile analysis results | High volume |
| `payments` | Stripe payment records | ~5-10% of analyses |
| `analysis_requests` | Rate limiting tracking | High volume (pruned) |
| `activity_log` | Admin activity tracking | Medium volume |
| `settings` | Application settings | ~20 |

---

## Entity Relationship Diagram

```
┌─────────────────────┐
│       users         │
├─────────────────────┤
│ id (PK)             │
│ name                │
│ email               │
│ password            │
│ created_at          │
│ updated_at          │
└─────────────────────┘

┌─────────────────────────────────────────────────────────────┐
│                        analyses                              │
├─────────────────────────────────────────────────────────────┤
│ id (PK)                                                      │
│ uuid (UNIQUE)                                                │
│ github_username                                              │
│ status (enum: pending, processing, completed, failed)        │
│ overall_score (0-100)                                        │
│ profile_score (0-100)                                        │
│ projects_score (0-100)                                       │
│ consistency_score (0-100)                                    │
│ technical_score (0-100)                                      │
│ community_score (0-100)                                      │
│ github_data (JSON)                                           │
│ ai_analysis (JSON)                                           │
│ is_paid (boolean)                                            │
│ stripe_payment_id                                            │
│ paid_at (timestamp)                                          │
│ ip_address                                                   │
│ user_agent                                                   │
│ error_message                                                │
│ completed_at (timestamp)                                     │
│ created_at                                                   │
│ updated_at                                                   │
└─────────────────────────────────────────────────────────────┘
                              │
                              │ 1:1
                              ▼
┌─────────────────────────────────────────────────────────────┐
│                        payments                              │
├─────────────────────────────────────────────────────────────┤
│ id (PK)                                                      │
│ analysis_id (FK → analyses.id)                               │
│ stripe_session_id (UNIQUE)                                   │
│ stripe_payment_intent                                        │
│ amount_cents                                                 │
│ currency                                                     │
│ status (enum: pending, completed, failed, refunded)          │
│ customer_email                                               │
│ metadata (JSON)                                              │
│ created_at                                                   │
│ updated_at                                                   │
└─────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────┐
│                    analysis_requests                         │
├─────────────────────────────────────────────────────────────┤
│ id (PK)                                                      │
│ ip_address                                                   │
│ github_username                                              │
│ created_at                                                   │
└─────────────────────────────────────────────────────────────┘
```

---

## Migrations

### 1. Create Analyses Table

```php
<?php

// database/migrations/2024_01_01_000001_create_analyses_table.php

use App\Enums\AnalysisStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('analyses', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('github_username', 39);
            
            // Status
            $table->string('status', 20)->default(AnalysisStatus::PENDING->value);
            
            // Scores (0-100)
            $table->unsignedTinyInteger('overall_score')->nullable();
            $table->unsignedTinyInteger('profile_score')->nullable();
            $table->unsignedTinyInteger('projects_score')->nullable();
            $table->unsignedTinyInteger('consistency_score')->nullable();
            $table->unsignedTinyInteger('technical_score')->nullable();
            $table->unsignedTinyInteger('community_score')->nullable();
            
            // Raw data (JSON)
            $table->json('github_data')->nullable();
            $table->json('ai_analysis')->nullable();
            
            // Payment
            $table->boolean('is_paid')->default(false);
            $table->string('stripe_payment_id')->nullable();
            $table->timestamp('paid_at')->nullable();
            
            // Meta
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('completed_at')->nullable();
            
            $table->timestamps();
            
            // Indexes
            $table->index('github_username');
            $table->index('status');
            $table->index('created_at');
            $table->index(['github_username', 'created_at']);
            $table->index(['is_paid', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('analyses');
    }
};
```

### 2. Create Payments Table

```php
<?php

// database/migrations/2024_01_01_000002_create_payments_table.php

use App\Enums\PaymentStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('analysis_id')
                ->constrained('analyses')
                ->cascadeOnDelete();
            
            // Stripe
            $table->string('stripe_session_id')->unique();
            $table->string('stripe_payment_intent')->nullable();
            
            // Amount
            $table->unsignedInteger('amount_cents');
            $table->char('currency', 3)->default('USD');
            
            // Status
            $table->string('status', 20)->default(PaymentStatus::PENDING->value);
            
            // Customer
            $table->string('customer_email')->nullable();
            $table->json('metadata')->nullable();
            
            $table->timestamps();
            
            // Indexes
            $table->index('stripe_payment_intent');
            $table->index('status');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
```

### 3. Create Analysis Requests Table (Rate Limiting)

```php
<?php

// database/migrations/2024_01_01_000003_create_analysis_requests_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('analysis_requests', function (Blueprint $table) {
            $table->id();
            $table->string('ip_address', 45);
            $table->string('github_username', 39);
            $table->timestamp('created_at')->useCurrent();
            
            // Indexes for rate limit queries
            $table->index(['ip_address', 'created_at']);
            $table->index(['github_username', 'created_at']);
            $table->index('created_at'); // For cleanup
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('analysis_requests');
    }
};
```

### 4. Add Filament Admin Tables

```php
<?php

// database/migrations/2024_01_01_000004_add_filament_columns_to_users_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_admin')->default(false)->after('password');
            $table->string('avatar')->nullable()->after('is_admin');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['is_admin', 'avatar']);
        });
    }
};
```

---

## Models

### Analysis Model

```php
<?php

namespace App\Models;

use App\Enums\AnalysisStatus;
use App\Enums\ScoreLevel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Analysis extends Model
{
    use HasFactory, LogsActivity;
    
    /**
     * The attributes that are mass assignable.
     */
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
        'error_message',
        'completed_at',
    ];
    
    /**
     * The attributes that should be cast.
     */
    protected $casts = [
        'status' => AnalysisStatus::class,
        'overall_score' => 'integer',
        'profile_score' => 'integer',
        'projects_score' => 'integer',
        'consistency_score' => 'integer',
        'technical_score' => 'integer',
        'community_score' => 'integer',
        'github_data' => 'array',
        'ai_analysis' => 'array',
        'is_paid' => 'boolean',
        'paid_at' => 'datetime',
        'completed_at' => 'datetime',
    ];
    
    /**
     * The attributes that should be hidden for serialization.
     */
    protected $hidden = [
        'id',
        'ip_address',
        'user_agent',
    ];
    
    /**
     * Get the route key name for Laravel routing.
     */
    public function getRouteKeyName(): string
    {
        return 'uuid';
    }
    
    // =========================================
    // RELATIONSHIPS
    // =========================================
    
    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class);
    }
    
    // =========================================
    // SCOPES
    // =========================================
    
    public function scopePending($query)
    {
        return $query->where('status', AnalysisStatus::PENDING);
    }
    
    public function scopeProcessing($query)
    {
        return $query->where('status', AnalysisStatus::PROCESSING);
    }
    
    public function scopeCompleted($query)
    {
        return $query->where('status', AnalysisStatus::COMPLETED);
    }
    
    public function scopeFailed($query)
    {
        return $query->where('status', AnalysisStatus::FAILED);
    }
    
    public function scopePaid($query)
    {
        return $query->where('is_paid', true);
    }
    
    public function scopeUnpaid($query)
    {
        return $query->where('is_paid', false);
    }
    
    public function scopeRecentForUsername($query, string $username, int $hours = 24)
    {
        return $query
            ->where('github_username', $username)
            ->where('created_at', '>', now()->subHours($hours))
            ->completed();
    }
    
    public function scopeForIp($query, string $ip)
    {
        return $query->where('ip_address', $ip);
    }
    
    // =========================================
    // ACCESSORS
    // =========================================
    
    /**
     * Get the score level classification.
     */
    public function getScoreLevelAttribute(): ScoreLevel
    {
        return ScoreLevel::fromScore($this->overall_score);
    }
    
    /**
     * Get the human-readable score label.
     */
    public function getScoreLabelAttribute(): string
    {
        return $this->scoreLevel->label();
    }
    
    /**
     * Get all category scores as an array.
     */
    public function getCategoryScoresAttribute(): array
    {
        return [
            'profile' => $this->profile_score,
            'projects' => $this->projects_score,
            'consistency' => $this->consistency_score,
            'technical' => $this->technical_score,
            'community' => $this->community_score,
        ];
    }
    
    /**
     * Get deal breakers from AI analysis.
     */
    public function getDealBreakersAttribute(): array
    {
        return $this->ai_analysis['deal_breakers'] ?? [];
    }
    
    /**
     * Get the first impression from AI analysis.
     */
    public function getFirstImpressionAttribute(): ?string
    {
        return $this->ai_analysis['first_impression'] ?? null;
    }
    
    /**
     * Get the summary from AI analysis.
     */
    public function getSummaryAttribute(): ?string
    {
        return $this->ai_analysis['summary'] ?? null;
    }
    
    /**
     * Check if analysis is complete.
     */
    public function getIsCompleteAttribute(): bool
    {
        return $this->status === AnalysisStatus::COMPLETED;
    }
    
    /**
     * Check if analysis is still processing.
     */
    public function getIsProcessingAttribute(): bool
    {
        return in_array($this->status, [
            AnalysisStatus::PENDING,
            AnalysisStatus::PROCESSING,
        ]);
    }
    
    // =========================================
    // METHODS
    // =========================================
    
    /**
     * Mark the analysis as processing.
     */
    public function markAsProcessing(): void
    {
        $this->update(['status' => AnalysisStatus::PROCESSING]);
    }
    
    /**
     * Mark the analysis as completed.
     */
    public function markAsCompleted(array $data): void
    {
        $this->update([
            'status' => AnalysisStatus::COMPLETED,
            'completed_at' => now(),
            ...$data,
        ]);
    }
    
    /**
     * Mark the analysis as failed.
     */
    public function markAsFailed(string $message): void
    {
        $this->update([
            'status' => AnalysisStatus::FAILED,
            'error_message' => $message,
        ]);
    }
    
    /**
     * Unlock full report after payment.
     */
    public function unlock(string $paymentId): void
    {
        $this->update([
            'is_paid' => true,
            'stripe_payment_id' => $paymentId,
            'paid_at' => now(),
        ]);
    }
    
    /**
     * Get free tier data only.
     */
    public function getFreeReport(): array
    {
        return [
            'overall_score' => $this->overall_score,
            'category_scores' => $this->category_scores,
            'deal_breakers' => array_slice($this->deal_breakers, 0, 3),
            'first_impression' => $this->first_impression,
            'score_level' => $this->score_level->value,
            'score_label' => $this->score_label,
        ];
    }
    
    /**
     * Get full report data (paid only).
     */
    public function getFullReport(): array
    {
        if (!$this->is_paid) {
            return $this->getFreeReport();
        }
        
        return [
            ...$this->getFreeReport(),
            'summary' => $this->summary,
            'categories' => $this->ai_analysis['categories'] ?? [],
            'deal_breakers' => $this->deal_breakers,
            'top_projects_analysis' => $this->ai_analysis['top_projects_analysis'] ?? [],
            'improvement_checklist' => $this->ai_analysis['improvement_checklist'] ?? [],
            'strengths' => $this->ai_analysis['strengths'] ?? [],
            'recruiter_perspective' => $this->ai_analysis['recruiter_perspective'] ?? null,
        ];
    }
    
    // =========================================
    // ACTIVITY LOG
    // =========================================
    
    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['status', 'is_paid', 'overall_score'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs();
    }
}
```

### Payment Model

```php
<?php

namespace App\Models;

use App\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'analysis_id',
        'stripe_session_id',
        'stripe_payment_intent',
        'amount_cents',
        'currency',
        'status',
        'customer_email',
        'metadata',
    ];
    
    protected $casts = [
        'status' => PaymentStatus::class,
        'amount_cents' => 'integer',
        'metadata' => 'array',
    ];
    
    // =========================================
    // RELATIONSHIPS
    // =========================================
    
    public function analysis(): BelongsTo
    {
        return $this->belongsTo(Analysis::class);
    }
    
    // =========================================
    // SCOPES
    // =========================================
    
    public function scopeCompleted($query)
    {
        return $query->where('status', PaymentStatus::COMPLETED);
    }
    
    public function scopePending($query)
    {
        return $query->where('status', PaymentStatus::PENDING);
    }
    
    // =========================================
    // ACCESSORS
    // =========================================
    
    /**
     * Get amount in dollars.
     */
    public function getAmountAttribute(): float
    {
        return $this->amount_cents / 100;
    }
    
    /**
     * Get formatted amount.
     */
    public function getFormattedAmountAttribute(): string
    {
        return '$' . number_format($this->amount, 2);
    }
    
    // =========================================
    // METHODS
    // =========================================
    
    public function markAsCompleted(string $paymentIntent): void
    {
        $this->update([
            'status' => PaymentStatus::COMPLETED,
            'stripe_payment_intent' => $paymentIntent,
        ]);
        
        $this->analysis->unlock($paymentIntent);
    }
    
    public function markAsFailed(): void
    {
        $this->update(['status' => PaymentStatus::FAILED]);
    }
    
    public function markAsRefunded(): void
    {
        $this->update(['status' => PaymentStatus::REFUNDED]);
        
        $this->analysis->update([
            'is_paid' => false,
            'paid_at' => null,
        ]);
    }
}
```

### AnalysisRequest Model

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AnalysisRequest extends Model
{
    public $timestamps = false;
    
    protected $fillable = [
        'ip_address',
        'github_username',
        'created_at',
    ];
    
    protected $casts = [
        'created_at' => 'datetime',
    ];
    
    /**
     * Count requests from IP in time window.
     */
    public static function countFromIp(string $ip, int $minutes = 60): int
    {
        return static::where('ip_address', $ip)
            ->where('created_at', '>', now()->subMinutes($minutes))
            ->count();
    }
    
    /**
     * Count requests for username in time window.
     */
    public static function countForUsername(string $username, int $minutes = 60): int
    {
        return static::where('github_username', $username)
            ->where('created_at', '>', now()->subMinutes($minutes))
            ->count();
    }
    
    /**
     * Record a new request.
     */
    public static function record(string $ip, string $username): static
    {
        return static::create([
            'ip_address' => $ip,
            'github_username' => $username,
            'created_at' => now(),
        ]);
    }
    
    /**
     * Prune old records.
     */
    public static function pruneOld(int $hoursOld = 24): int
    {
        return static::where('created_at', '<', now()->subHours($hoursOld))
            ->delete();
    }
}
```

### User Model (With Filament)

```php
<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable implements FilamentUser
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'is_admin',
        'avatar',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
        ];
    }
    
    /**
     * Determine if user can access Filament panel.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        return $this->is_admin;
    }
}
```

---

## Enums

### AnalysisStatus Enum

```php
<?php

namespace App\Enums;

enum AnalysisStatus: string
{
    case PENDING = 'pending';
    case PROCESSING = 'processing';
    case COMPLETED = 'completed';
    case FAILED = 'failed';
    
    public function label(): string
    {
        return match($this) {
            self::PENDING => 'Pending',
            self::PROCESSING => 'Processing',
            self::COMPLETED => 'Completed',
            self::FAILED => 'Failed',
        };
    }
    
    public function color(): string
    {
        return match($this) {
            self::PENDING => 'gray',
            self::PROCESSING => 'warning',
            self::COMPLETED => 'success',
            self::FAILED => 'danger',
        };
    }
    
    public function icon(): string
    {
        return match($this) {
            self::PENDING => 'heroicon-o-clock',
            self::PROCESSING => 'heroicon-o-arrow-path',
            self::COMPLETED => 'heroicon-o-check-circle',
            self::FAILED => 'heroicon-o-x-circle',
        };
    }
}
```

### PaymentStatus Enum

```php
<?php

namespace App\Enums;

enum PaymentStatus: string
{
    case PENDING = 'pending';
    case COMPLETED = 'completed';
    case FAILED = 'failed';
    case REFUNDED = 'refunded';
    
    public function label(): string
    {
        return match($this) {
            self::PENDING => 'Pending',
            self::COMPLETED => 'Completed',
            self::FAILED => 'Failed',
            self::REFUNDED => 'Refunded',
        };
    }
    
    public function color(): string
    {
        return match($this) {
            self::PENDING => 'warning',
            self::COMPLETED => 'success',
            self::FAILED => 'danger',
            self::REFUNDED => 'gray',
        };
    }
}
```

### ScoreLevel Enum

```php
<?php

namespace App\Enums;

enum ScoreLevel: string
{
    case EXCEPTIONAL = 'exceptional';
    case STRONG = 'strong';
    case GOOD = 'good';
    case AVERAGE = 'average';
    case BELOW_AVERAGE = 'below_average';
    case POOR = 'poor';
    
    public function label(): string
    {
        return match($this) {
            self::EXCEPTIONAL => 'Exceptional',
            self::STRONG => 'Strong',
            self::GOOD => 'Good',
            self::AVERAGE => 'Average',
            self::BELOW_AVERAGE => 'Below Average',
            self::POOR => 'Needs Work',
        };
    }
    
    public function color(): string
    {
        return match($this) {
            self::EXCEPTIONAL => '#22c55e',
            self::STRONG => '#22c55e',
            self::GOOD => '#84cc16',
            self::AVERAGE => '#eab308',
            self::BELOW_AVERAGE => '#f97316',
            self::POOR => '#ef4444',
        };
    }
    
    public function description(): string
    {
        return match($this) {
            self::EXCEPTIONAL => 'Top 5% of profiles. Ready for FAANG.',
            self::STRONG => 'Would get interviews at most companies.',
            self::GOOD => 'Some clear improvements needed.',
            self::AVERAGE => 'Needs work to stand out.',
            self::BELOW_AVERAGE => 'Several red flags present.',
            self::POOR => 'Significant issues. Needs major overhaul.',
        };
    }
    
    public static function fromScore(?int $score): self
    {
        if ($score === null) {
            return self::POOR;
        }
        
        return match(true) {
            $score >= 90 => self::EXCEPTIONAL,
            $score >= 80 => self::STRONG,
            $score >= 70 => self::GOOD,
            $score >= 60 => self::AVERAGE,
            $score >= 50 => self::BELOW_AVERAGE,
            default => self::POOR,
        };
    }
}
```

### ScoreCategory Enum

```php
<?php

namespace App\Enums;

enum ScoreCategory: string
{
    case PROFILE = 'profile';
    case PROJECTS = 'projects';
    case CONSISTENCY = 'consistency';
    case TECHNICAL = 'technical';
    case COMMUNITY = 'community';
    
    public function label(): string
    {
        return match($this) {
            self::PROFILE => 'Profile Completeness',
            self::PROJECTS => 'Project Quality',
            self::CONSISTENCY => 'Contribution Consistency',
            self::TECHNICAL => 'Technical Signals',
            self::COMMUNITY => 'Community Engagement',
        };
    }
    
    public function weight(): float
    {
        return match($this) {
            self::PROFILE => 0.15,
            self::PROJECTS => 0.30,
            self::CONSISTENCY => 0.20,
            self::TECHNICAL => 0.20,
            self::COMMUNITY => 0.15,
        };
    }
    
    public function description(): string
    {
        return match($this) {
            self::PROFILE => 'Bio, avatar, location, website, README',
            self::PROJECTS => 'Top repos: descriptions, READMEs, stars, activity',
            self::CONSISTENCY => 'Commit frequency, patterns, gaps',
            self::TECHNICAL => 'Languages used, diversity, modern stack',
            self::COMMUNITY => 'PRs to others, issues, followers/following ratio',
        };
    }
}
```

---

## Factories & Seeders

### AnalysisFactory

```php
<?php

namespace Database\Factories;

use App\Enums\AnalysisStatus;
use App\Models\Analysis;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class AnalysisFactory extends Factory
{
    protected $model = Analysis::class;

    public function definition(): array
    {
        return [
            'uuid' => Str::uuid(),
            'github_username' => fake()->userName(),
            'status' => AnalysisStatus::COMPLETED,
            'overall_score' => fake()->numberBetween(30, 95),
            'profile_score' => fake()->numberBetween(20, 100),
            'projects_score' => fake()->numberBetween(20, 100),
            'consistency_score' => fake()->numberBetween(20, 100),
            'technical_score' => fake()->numberBetween(20, 100),
            'community_score' => fake()->numberBetween(20, 100),
            'github_data' => $this->fakeGitHubData(),
            'ai_analysis' => $this->fakeAIAnalysis(),
            'is_paid' => fake()->boolean(20),
            'ip_address' => fake()->ipv4(),
            'completed_at' => now(),
        ];
    }
    
    public function pending(): static
    {
        return $this->state(fn () => [
            'status' => AnalysisStatus::PENDING,
            'overall_score' => null,
            'ai_analysis' => null,
            'completed_at' => null,
        ]);
    }
    
    public function processing(): static
    {
        return $this->state(fn () => [
            'status' => AnalysisStatus::PROCESSING,
            'overall_score' => null,
            'ai_analysis' => null,
            'completed_at' => null,
        ]);
    }
    
    public function failed(): static
    {
        return $this->state(fn () => [
            'status' => AnalysisStatus::FAILED,
            'overall_score' => null,
            'ai_analysis' => null,
            'error_message' => 'GitHub API rate limit exceeded',
            'completed_at' => null,
        ]);
    }
    
    public function paid(): static
    {
        return $this->state(fn () => [
            'is_paid' => true,
            'paid_at' => now(),
            'stripe_payment_id' => 'pi_' . Str::random(24),
        ]);
    }
    
    private function fakeGitHubData(): array
    {
        return [
            'user' => [
                'login' => fake()->userName(),
                'name' => fake()->name(),
                'bio' => fake()->sentence(),
                'public_repos' => fake()->numberBetween(5, 50),
                'followers' => fake()->numberBetween(10, 500),
            ],
        ];
    }
    
    private function fakeAIAnalysis(): array
    {
        return [
            'summary' => fake()->paragraph(),
            'first_impression' => fake()->sentence(),
            'deal_breakers' => [
                ['issue' => 'No profile README', 'fix' => 'Create a README.md'],
            ],
            'categories' => [],
            'strengths' => [fake()->sentence()],
        ];
    }
}
```

### DatabaseSeeder

```php
<?php

namespace Database\Seeders;

use App\Models\Analysis;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Create admin user
        User::factory()->create([
            'name' => 'Admin',
            'email' => 'admin@gitroast.dev',
            'is_admin' => true,
        ]);
        
        // Create sample analyses
        Analysis::factory(50)->create();
        Analysis::factory(10)->paid()->create();
        Analysis::factory(5)->pending()->create();
        Analysis::factory(3)->failed()->create();
    }
}
```

---

## Indexes & Performance

### Recommended Indexes

```sql
-- Primary queries optimized
CREATE INDEX idx_analyses_username_created ON analyses(github_username, created_at);
CREATE INDEX idx_analyses_status ON analyses(status);
CREATE INDEX idx_analyses_is_paid_created ON analyses(is_paid, created_at);

-- Rate limiting queries
CREATE INDEX idx_requests_ip_created ON analysis_requests(ip_address, created_at);
CREATE INDEX idx_requests_username_created ON analysis_requests(github_username, created_at);

-- Payments
CREATE INDEX idx_payments_stripe_session ON payments(stripe_session_id);
CREATE INDEX idx_payments_status ON payments(status);
```

### Query Optimization Tips

1. **Always use indexes** for username and date lookups
2. **Paginate large results** with cursor pagination for admin
3. **Cache recent analyses** for 24 hours
4. **Prune old rate limit records** daily

---

## Next Steps

1. Run migrations: `php artisan migrate`
2. Seed data: `php artisan db:seed`
3. Continue to [API Documentation](05-API.md)
