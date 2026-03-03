<?php

namespace App\Models;

use App\Enums\PaymentStatus;
use App\Enums\PaymentMethod;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Carbon\Carbon;

class Payment extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'quote_id',
        'company_id',
        'space_owner_id',
        'amount',
        'platform_fee',
        'net_amount',
        'payment_method',
        'status',
        'gateway',
        'gateway_transaction_id',
        'gateway_response',
        'payment_url',
        'payment_code',
        'paid_at',
        'expires_at',
        'metadata',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'platform_fee' => 'decimal:2',
        'net_amount' => 'decimal:2',
        'status' => PaymentStatus::class,
        'payment_method' => PaymentMethod::class,
        'gateway_response' => 'array',
        'metadata' => 'array',
        'paid_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    protected $appends = [
        'formatted_amount',
        'formatted_fee',
        'formatted_net_amount',
        'is_expired',
    ];

    // Relacionamentos
    public function quote(): BelongsTo
    {
        return $this->belongsTo(Quote::class);
    }

    public function payer(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'company_id');
    }

    public function spaceOwner(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'space_owner_id');
    }

    // Accessors
    public function getFormattedAmountAttribute(): string
    {
        return 'R$ ' . number_format($this->amount, 2, ',', '.');
    }

    public function getFormattedFeeAttribute(): string
    {
        return 'R$ ' . number_format($this->platform_fee, 2, ',', '.');
    }

    public function getFormattedNetAmountAttribute(): string
    {
        return 'R$ ' . number_format($this->net_amount, 2, ',', '.');
    }

    public function getIsExpiredAttribute(): bool
    {
        if (!$this->expires_at) {
            return false;
        }
        return $this->expires_at->isPast() && $this->status !== PaymentStatus::Paid;
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', PaymentStatus::Pending);
    }

    public function scopePaid($query)
    {
        return $query->where('status', PaymentStatus::Paid);
    }

    public function scopeFailed($query)
    {
        return $query->where('status', PaymentStatus::Failed);
    }

    public function scopeProcessing($query)
    {
        return $query->where('status', PaymentStatus::Processing);
    }

    // Métodos
    public function calculateFee(float $amount): float
    {
        $feePercentage = config('payment.platform_fee_percentage', 10);
        return round(($amount * $feePercentage) / 100, 2);
    }

    public function calculateNetAmount(float $amount, float $fee): float
    {
        return round($amount - $fee, 2);
    }

    public function isExpired(): bool
    {
        return $this->is_expired;
    }

    public function canRefund(): bool
    {
        return $this->status === PaymentStatus::Paid;
    }

    public function canProcess(): bool
    {
        return $this->status === PaymentStatus::Pending && !$this->isExpired();
    }

    public function markAsProcessing(): void
    {
        $this->update(['status' => PaymentStatus::Processing]);
    }

    public function markAsPaid(?Carbon $paidAt = null): void
    {
        $this->update([
            'status' => PaymentStatus::Paid,
            'paid_at' => $paidAt ?? now(),
        ]);
    }

    public function markAsFailed(): void
    {
        $this->update(['status' => PaymentStatus::Failed]);
    }

    public function markAsRefunded(?string $reason = null): void
    {
        $this->update([
            'status' => PaymentStatus::Refunded,
            'metadata' => array_merge($this->metadata ?? [], ['refund_reason' => $reason]),
        ]);
    }
}

