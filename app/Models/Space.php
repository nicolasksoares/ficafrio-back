<?php

namespace App\Models;

use App\Enums\SpaceType;
use App\Enums\SpaceStatus;
use App\Helpers\ImageUrlHelper;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Space extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id', 'name', 'description', 'zip_code', 'address', 'number',
        'district', 'city', 'state', 'temp_min', 'temp_max', 'capacity',
        'capacity_unit', 'available_pallet_positions', 'available_from', 'available_until',
        'contact_email', 'contact_phone', 'contact_name', 'type',
        'has_anvisa', 'has_security', 'has_generator', 'has_dock',
        'operating_hours', 'allows_extended_hours', 'active','status', 'main_image'
    ];

    protected $casts = [
        'has_anvisa' => 'boolean', 
        'has_security' => 'boolean',
        'has_generator' => 'boolean', 
        'has_dock' => 'boolean',
        'allows_extended_hours' => 'boolean', 
        'active' => 'boolean',
        'status' => SpaceStatus::class,
        'type' => SpaceType::class, 
        
        // --- AQUI ESTÁ A CORREÇÃO ---
        // Força o formato YYYY-MM-DD na serialização JSON
        'available_from' => 'date:Y-m-d', 
        'available_until' => 'date:Y-m-d',
    ];

    protected $appends = ['main_image_url'];

    /**
     * Accessor para URL da imagem principal
     * Usa helper centralizado para suporte a CDN/S3
     */
    public function getMainImageUrlAttribute(): ?string
    {
        return $this->main_image ? ImageUrlHelper::url($this->main_image) : null;
    }

    /**
     * Limpa cache ao atualizar/deletar imagem principal
     */
    protected static function booted(): void
    {
        static::updating(function ($space) {
            // Limpa cache da imagem antiga se foi alterada
            if ($space->isDirty('main_image')) {
                $oldPath = $space->getOriginal('main_image');
                ImageUrlHelper::clearCache($oldPath);
            }
        });

        static::deleted(function ($space) {
            ImageUrlHelper::clearCache($space->main_image);
        });
    }

    public function company(): BelongsTo {
        return $this->belongsTo(Company::class);
    }

    public function photos(): HasMany {
        return $this->hasMany(SpacePhoto::class);
    }
}