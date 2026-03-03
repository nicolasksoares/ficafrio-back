<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class QuoteResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $isExpired = $this->valid_until ? $this->valid_until->isPast() : false;
        return [
            'id' => $this->id,
            'status' => strtolower($this->status instanceof \UnitEnum ? $this->status->value : $this->status),
            'price' => $this->price ? (float) $this->price : null,
            'valid_until' => $this->valid_until,
            'is_expired' => $isExpired,
            'can_accept' => !$isExpired && $this->status->value === 'respondido',
            'rejection_reason' => $this->rejection_reason,
            'created_at' => $this->created_at->toIso8601String(),
            
            'storage_request' => [
                'id' => $this->storageRequest->id,
                'company_id' => $this->storageRequest->company_id,
                'product_type' => $this->storageRequest->product_type,
                'description' => $this->storageRequest->description,
                'quantity' => $this->storageRequest->quantity,
                'start_date' => $this->storageRequest->start_date,
                'end_date' => $this->storageRequest->end_date,
                'company' => [
                    'trade_name' => $this->storageRequest->company->trade_name ?? 'Cliente'
                ]
            ],

            'space' => [
                'id' => $this->space->id,
                'company_id' => $this->space->company_id,
                'name' => $this->space->name,
                'city' => $this->space->city,
                'state' => $this->space->state,
                'company' => [
                    'trade_name' => $this->space->company->trade_name ?? 'Parceiro'
                ]
            ],

            'histories' => $this->histories->map(function ($history) {
                return [
                    'id' => $history->id,
                    'action' => $history->action,
                    'description' => $history->description,
                    'company_name' => $history->company->trade_name ?? 'Sistema',
                    'created_at' => $history->created_at->toIso8601String(),
                ];
            }),

            'payment_id' => $this->payment_id,
            'payment' => $this->payment ? [
                'id' => $this->payment->id,
                'status' => $this->payment->status->value,
                'payment_method' => $this->payment->payment_method?->value,
                'formatted_amount' => $this->payment->formatted_amount,
            ] : null,
        ];
    }
}