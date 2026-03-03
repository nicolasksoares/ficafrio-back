<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'quote_id' => $this->quote_id,
            'amount' => (float) $this->amount,
            'platform_fee' => (float) $this->platform_fee,
            'net_amount' => (float) $this->net_amount,
            'formatted_amount' => $this->formatted_amount,
            'formatted_fee' => $this->formatted_fee,
            'formatted_net_amount' => $this->formatted_net_amount,
            'payment_method' => $this->payment_method?->value,
            'payment_method_label' => $this->payment_method?->label(),
            'status' => $this->status->value,
            'status_label' => $this->status->label(),
            'status_color' => $this->status->color(),
            'gateway' => $this->gateway,
            'gateway_transaction_id' => $this->gateway_transaction_id,
            'payment_url' => $this->payment_url,
            'payment_code' => $this->payment_code,
            'is_expired' => $this->is_expired,
            'paid_at' => $this->paid_at?->toIso8601String(),
            'expires_at' => $this->expires_at?->toIso8601String(),
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
            
            'quote' => [
                'id' => $this->quote->id,
                'price' => (float) $this->quote->price,
                'status' => $this->quote->status->value,
            ],
            
            'payer' => [
                'id' => $this->payer->id,
                'trade_name' => $this->payer->trade_name,
            ],
            
            'space_owner' => [
                'id' => $this->spaceOwner->id,
                'trade_name' => $this->spaceOwner->trade_name,
            ],
        ];
    }
}

