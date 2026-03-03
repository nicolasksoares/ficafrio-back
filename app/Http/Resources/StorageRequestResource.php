<?php

namespace App\Http\Resources;

use App\Models\StorageRequest;
use App\Enums\ProductType;
use App\Enums\RequestStatus;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin StorageRequest
 */
class StorageRequestResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title, // <--- ADICIONADO (Frontend precisa disso)
            
            // Dados brutos para lógica (usados pelo TS)
            'product_type' => $this->product_type instanceof ProductType 
                ? $this->product_type->label() 
                : ucfirst((string)$this->product_type),
            
            'quantity' => $this->quantity, // <--- Valor numérico puro
            'unit' => $this->unit,         // <--- Unidade separada
            
            // Dados formatados para exibição (opcional, se quiser manter compatibilidade)
            'formatted_volume' => "{$this->quantity} " . ucfirst((string)$this->unit), 
            
            'description' => $this->description,
            
            'status' => $this->status instanceof RequestStatus 
                ? $this->status->label() 
                : ucfirst((string)$this->status),
            
            'temp_min' => $this->temp_min,
            'temp_max' => $this->temp_max,
            
            'start_date' => $this->start_date ? $this->start_date->format('Y-m-d') : null,
            'end_date' => $this->end_date ? $this->end_date->format('Y-m-d') : null,
            
            'target_city' => $this->target_city,
            'target_state' => $this->target_state,
        ];
    }
}