<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AdminQuoteResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $isExpired = $this->valid_until ? $this->valid_until->isPast() : false;
        $clientCompany = $this->storageRequest?->company ?? null;
        $partnerCompany = $this->space?->company ?? null;

        $status = strtolower($this->status instanceof \UnitEnum ? $this->status->value : $this->status);

        return [
            'id' => $this->id,
            'status' => $status,
            'price' => $this->price ? (float) $this->price : null,
            'valid_until' => $this->valid_until,
            'is_expired' => $isExpired,
            'created_at' => $this->created_at->toIso8601String(),
            'pending_since' => $this->created_at->diffForHumans(),
            'admin_approved_at' => $this->admin_approved_at?->toIso8601String(),
            'rejection_reason' => $this->rejection_reason,
            'deleted_at' => $this->deleted_at?->toIso8601String(),

            'storage_request' => [
                'id' => $this->storageRequest->id,
                'company_id' => $this->storageRequest->company_id,
                'product_type' => $this->storageRequest->product_type,
                'quantity' => $this->storageRequest->quantity,
                'start_date' => $this->storageRequest->start_date,
                'end_date' => $this->storageRequest->end_date,
                'demandante' => [
                    'trade_name' => $clientCompany->trade_name ?? 'Cliente',
                    'city' => $clientCompany->city ?? null,
                    'state' => $clientCompany->state ?? null,
                    'contact_email' => $this->storageRequest->contact_email ?? $clientCompany->email ?? null,
                    'contact_phone' => $this->storageRequest->contact_phone ?? $clientCompany->phone ?? null,
                    'contact_name' => $this->storageRequest->contact_name ?? $clientCompany->trade_name ?? null,
                    'active' => $clientCompany->active ?? true,
                ],
            ],

            'space' => [
                'id' => $this->space->id,
                'company_id' => $this->space->company_id,
                'name' => $this->space->name,
                'city' => $this->space->city,
                'state' => $this->space->state,
                'ofertante' => [
                    'trade_name' => $partnerCompany->trade_name ?? 'Parceiro',
                    'city' => $partnerCompany->city ?? null,
                    'state' => $partnerCompany->state ?? null,
                    'contact_email' => $this->space->contact_email ?? $partnerCompany->email ?? null,
                    'contact_phone' => $this->space->contact_phone ?? $partnerCompany->phone ?? null,
                    'contact_name' => $this->space->contact_name ?? $partnerCompany->trade_name ?? null,
                    'active' => $partnerCompany->active ?? true,
                ],
            ],
        ];
    }
}
