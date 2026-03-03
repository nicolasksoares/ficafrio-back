<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuoteAudit extends Model
{
    protected $fillable = [
        'quote_id',
        'admin_id',
        'action',
        'old_status',
        'new_status',
        'old_price',
        'new_price',
        'reason',
    ];

    protected $casts = [
        'old_price' => 'decimal:2',
        'new_price' => 'decimal:2',
    ];

    public function quote(): BelongsTo
    {
        return $this->belongsTo(Quote::class);
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(Company::class, 'admin_id');
    }
}
