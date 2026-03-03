<?php

namespace App\Models;

use App\Helpers\ImageUrlHelper;
use App\Traits\HasImageUrl;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SpacePhoto extends Model
{
    use HasFactory, HasImageUrl;

    protected $fillable = ['space_id', 'path'];

    protected $appends = ['url'];

    /**
     * Accessor para URL da foto
     * Usa helper centralizado para consistência e cache
     */
    public function getUrlAttribute(): ?string
    {
        return $this->getImageUrl($this->path);
    }

    /**
     * Limpa cache ao deletar foto
     */
    protected static function booted(): void
    {
        static::deleted(function ($photo) {
            ImageUrlHelper::clearCache($photo->path);
        });
    }

    public function space(): BelongsTo
    {
        return $this->belongsTo(Space::class);
    }
}