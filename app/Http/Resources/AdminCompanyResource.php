<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminCompanyResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'legal_name' => $this->legal_name,
            'trade_name' => $this->trade_name,
            'cnpj' => $this->cnpj,
            'type' => $this->type->value ?? $this->type,
            'email' => $this->email,
            'phone' => $this->phone,
            'city' => $this->city,
            'state' => $this->state,
            'active' => $this->active,
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'spaces_count' => $this->spaces_count ?? 0,
            'storage_requests_count' => $this->storage_requests_count ?? 0,
        ];
    }
}
