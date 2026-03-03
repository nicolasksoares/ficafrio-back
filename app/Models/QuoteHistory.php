<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuoteHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'quote_id',
        'company_id',
        'action',
        'description',
    ];

    public function quote()
    {
        return $this->belongsTo(Quote::class);
    }

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}