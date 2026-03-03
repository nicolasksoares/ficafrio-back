<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CompanyResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'legal_name' => $this->legal_name,
            'trade_name' => $this->trade_name,
            'cnpj' => $this->cnpj,
            'type' => $this->type->value,
            'email' => $this->email,
            'phone' => $this->phone,
            'address_street' => $this->address_street,
            'address_number' => $this->address_number,
            'district' => $this->district,
            'city' => $this->city,
            'state' => $this->state,
            'zip_code' => $this->zip_code,
            'active' => $this->active,
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
        ];
    }
}