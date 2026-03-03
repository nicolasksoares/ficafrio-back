<?php

namespace App\Models;

use App\Enums\QuoteStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Quote extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'storage_request_id',
        'space_id',
        'price',
        'valid_until',
        'rejection_reason',
        'status',
        'payment_id',
        'admin_approved_at',
        'admin_approved_by',
    ];

    protected $casts = [
        'status' => QuoteStatus::class,
        'price' => 'decimal:2',
        'valid_until' => 'date',
        'admin_approved_at' => 'datetime',
    ];

    public function storageRequest(): BelongsTo
    {
        return $this->belongsTo(StorageRequest::class);
    }

    public function space(): BelongsTo
    {
        return $this->belongsTo(Space::class);
    }

    public function histories()
    {
        return $this->hasMany(QuoteHistory::class)->latest();
    }

    public function payment()
    {
        return $this->hasOne(Payment::class);
    }

    public function hasPayment(): bool
    {
        return $this->payment()->exists();
    }

    public function canCreatePayment(): bool
    {
        return $this->status === QuoteStatus::Aceito && !$this->hasPayment();
    }
}