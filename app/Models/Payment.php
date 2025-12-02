<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $analysis_id
 * @property string $stripe_session_id
 * @property string|null $stripe_payment_intent
 * @property int $amount_cents
 * @property string $currency
 * @property PaymentStatus $status
 * @property string|null $customer_email
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property-read Analysis $analysis
 */
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
    ];

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return [
            'status' => PaymentStatus::class,
            'amount_cents' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Analysis, $this>
     */
    public function analysis(): BelongsTo
    {
        return $this->belongsTo(Analysis::class);
    }

    /**
     * @param  Builder<Payment>  $query
     * @return Builder<Payment>
     */
    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('status', PaymentStatus::COMPLETED);
    }

    /**
     * @param  Builder<Payment>  $query
     * @return Builder<Payment>
     */
    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', PaymentStatus::PENDING);
    }

    /**
     * @param  Builder<Payment>  $query
     * @return Builder<Payment>
     */
    public function scopeFailed(Builder $query): Builder
    {
        return $query->where('status', PaymentStatus::FAILED);
    }

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
        $this->update([
            'status' => PaymentStatus::FAILED,
        ]);
    }

    public function getAmountFormatted(): string
    {
        return '$'.number_format($this->amount_cents / 100, 2);
    }
}
