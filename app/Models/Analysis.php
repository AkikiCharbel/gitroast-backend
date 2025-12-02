<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AnalysisStatus;
use App\Enums\ScoreLevel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property int $id
 * @property string $uuid
 * @property string $github_username
 * @property AnalysisStatus $status
 * @property int|null $overall_score
 * @property int|null $profile_score
 * @property int|null $projects_score
 * @property int|null $consistency_score
 * @property int|null $technical_score
 * @property int|null $community_score
 * @property array<string, mixed>|null $github_data
 * @property array<string, mixed>|null $ai_analysis
 * @property bool $is_paid
 * @property string|null $stripe_payment_id
 * @property \Illuminate\Support\Carbon|null $paid_at
 * @property string|null $ip_address
 * @property string|null $error_message
 * @property \Illuminate\Support\Carbon|null $completed_at
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property-read ScoreLevel $score_level
 * @property-read array<string, int|null> $category_scores
 * @property-read array<int, array<string, mixed>> $deal_breakers
 * @property-read array<int, array<string, mixed>> $improvement_checklist
 * @property-read array<int, string> $strengths
 * @property-read Payment|null $payment
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Payment> $payments
 */
class Analysis extends Model
{
    use HasFactory;

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
        'error_message',
        'completed_at',
    ];

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return [
            'status' => AnalysisStatus::class,
            'github_data' => 'array',
            'ai_analysis' => 'array',
            'is_paid' => 'boolean',
            'paid_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    /**
     * @return HasMany<Payment, $this>
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * @return HasOne<Payment, $this>
     */
    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class)->latestOfMany();
    }

    /**
     * @return Attribute<ScoreLevel, never>
     */
    protected function scoreLevel(): Attribute
    {
        return Attribute::make(
            get: fn (): ScoreLevel => ScoreLevel::fromScore($this->overall_score),
        );
    }

    /**
     * @return Attribute<array<string, int|null>, never>
     */
    protected function categoryScores(): Attribute
    {
        return Attribute::make(
            get: fn (): array => [
                'profile' => $this->profile_score,
                'projects' => $this->projects_score,
                'consistency' => $this->consistency_score,
                'technical' => $this->technical_score,
                'community' => $this->community_score,
            ],
        );
    }

    /**
     * @return Attribute<array<int, array<string, mixed>>, never>
     */
    protected function dealBreakers(): Attribute
    {
        return Attribute::make(
            get: fn (): array => $this->ai_analysis['deal_breakers'] ?? [],
        );
    }

    /**
     * @return Attribute<array<int, array<string, mixed>>, never>
     */
    protected function improvementChecklist(): Attribute
    {
        return Attribute::make(
            get: fn (): array => $this->ai_analysis['improvement_checklist'] ?? [],
        );
    }

    /**
     * @return Attribute<array<int, string>, never>
     */
    protected function strengths(): Attribute
    {
        return Attribute::make(
            get: fn (): array => $this->ai_analysis['strengths'] ?? [],
        );
    }

    /**
     * @param  Builder<Analysis>  $query
     * @return Builder<Analysis>
     */
    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', AnalysisStatus::COMPLETED);
    }

    /**
     * @param  Builder<Analysis>  $query
     * @return Builder<Analysis>
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', AnalysisStatus::PENDING);
    }

    /**
     * @param  Builder<Analysis>  $query
     * @return Builder<Analysis>
     */
    public function scopeProcessing(Builder $query): Builder
    {
        return $query->where('status', AnalysisStatus::PROCESSING);
    }

    /**
     * @param  Builder<Analysis>  $query
     * @return Builder<Analysis>
     */
    public function scopeFailed(Builder $query): Builder
    {
        return $query->where('status', AnalysisStatus::FAILED);
    }

    /**
     * @param  Builder<Analysis>  $query
     * @return Builder<Analysis>
     */
    public function scopePaid(Builder $query): Builder
    {
        return $query->where('is_paid', true);
    }

    /**
     * @param  Builder<Analysis>  $query
     * @return Builder<Analysis>
     */
    public function scopeUnpaid(Builder $query): Builder
    {
        return $query->where('is_paid', false);
    }

    public function markAsProcessing(): void
    {
        $this->update([
            'status' => AnalysisStatus::PROCESSING,
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function markAsCompleted(array $data): void
    {
        $this->update([
            'status' => AnalysisStatus::COMPLETED,
            'completed_at' => now(),
            ...$data,
        ]);
    }

    public function markAsFailed(string $errorMessage): void
    {
        $this->update([
            'status' => AnalysisStatus::FAILED,
            'error_message' => $errorMessage,
        ]);
    }

    public function unlock(string $stripePaymentId): void
    {
        $this->update([
            'is_paid' => true,
            'stripe_payment_id' => $stripePaymentId,
            'paid_at' => now(),
        ]);
    }

    public function isComplete(): bool
    {
        return $this->status === AnalysisStatus::COMPLETED;
    }

    public function isPending(): bool
    {
        return $this->status === AnalysisStatus::PENDING;
    }

    public function isProcessing(): bool
    {
        return $this->status === AnalysisStatus::PROCESSING;
    }

    public function hasFailed(): bool
    {
        return $this->status === AnalysisStatus::FAILED;
    }
}
