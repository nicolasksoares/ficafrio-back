<?php

namespace App\Models;

use App\Enums\ProductType;
use App\Enums\RequestStatus; // Importante
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class StorageRequest extends Model
{
    use HasFactory, SoftDeletes;

    protected $appends = ['is_active'];

    protected $fillable = [
        'company_id',
        'title',          // <--- Adicionado
        'category',       // <--- Adicionado
        'product_type',
        'description',
        'quantity',
        'unit',
        'temp_min',
        'temp_max',
        'start_date',
        'end_date',
        'status',
        'target_city',
        'target_state',
        'requester_message', 
        'proposed_price',
        'contact_name',   // <--- Adicionado
        'contact_phone',  // <--- Adicionado
        'contact_email',  // <--- Adicionado
    ];

    protected $casts = [
        'product_type' => ProductType::class,
        'status' => RequestStatus::class, // Recomendo adicionar isso se criou o Enum RequestStatus
        'start_date' => 'date',
        'end_date' => 'date',
        'proposed_price' => 'decimal:2',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    public function quotes()
    {
        return $this->hasMany(Quote::class);
    }

    public function getIsActiveAttribute(): bool
    {
        // Se usar Enum, a comparação deve ser com o Enum, não string
        if (isset($this->casts['status'])) {
             return $this->status === RequestStatus::Pendente;
        }
        return $this->status === 'pendente'; 
    }
}