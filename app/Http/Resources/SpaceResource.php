<?php

namespace App\Http\Resources;

use App\Helpers\ImageUrlHelper;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SpaceResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     * 
     * Otimizações:
     * - Usa helper centralizado para URLs
     * - Suporta CDN/S3 automaticamente
     * - Cache de URLs
     * - Lazy loading de relacionamentos
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            
            // Endereço
            'street_address' => $this->address,
            'address' => $this->address,
            'number' => $this->number,
            'district' => $this->district,
            'city' => $this->city,
            'state' => $this->state,
            'zip_code' => $this->zip_code,
            
            // Dados técnicos
            'min_temperature_celsius' => $this->temp_min,   
            'max_temperature_celsius' => $this->temp_max,
            'total_pallet_positions' => $this->capacity,
            'available_pallet_positions' => $this->available_pallet_positions,
            'available_from' => $this->available_from,
            'available_until' => $this->available_until,
            'chamber_type' => $this->type instanceof \UnitEnum ? $this->type->value : $this->type,
            'operating_hours' => $this->operating_hours,

            // Imagem principal - usa helper centralizado
            'main_image' => ImageUrlHelper::url($this->main_image),
            
            // Fotos - lazy loading com whenLoaded
            // O accessor url do modelo já usa o helper, então é otimizado
            'photos' => $this->whenLoaded('photos', function () {
                return $this->photos->map(fn($photo) => $photo->url)->filter();
            }),

            'status' => $this->status, 
            'active' => (bool) $this->active,
            
            'company' => $this->whenLoaded('company', function () {
                return [
                    'id' => $this->company->id,
                    'trade_name' => $this->company->trade_name,
                    'email' => $this->company->email,
                    'phone' => $this->company->phone,
                ];
            }),
            
            'created_at' => $this->created_at->toIso8601String(),
        ];
    }
}