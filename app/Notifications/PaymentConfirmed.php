<?php

namespace App\Notifications;

use App\Models\Payment;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class PaymentConfirmed extends Notification
{
    use Queueable;

    public function __construct(public Payment $payment) {}

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toArray($notifiable): array
    {
        $isPayer = $notifiable->id === $this->payment->company_id;
        
        return [
            'message' => $isPayer 
                ? 'Seu pagamento foi confirmado! O espaço está liberado para uso.'
                : 'Pagamento confirmado! Você receberá a transferência em breve.',
            'type' => 'payment',
            'payment_id' => $this->payment->id,
            'quote_id' => $this->payment->quote_id,
            'amount' => (float) $this->payment->amount,
            'formatted_amount' => $this->payment->formatted_amount,
            'net_amount' => (float) $this->payment->net_amount,
            'formatted_net_amount' => $this->payment->formatted_net_amount,
            'platform_fee' => (float) $this->payment->platform_fee,
            'formatted_fee' => $this->payment->formatted_fee,
            'status' => $this->payment->status->value,
            'paid_at' => $this->payment->paid_at?->toIso8601String(),
            'space_name' => $this->payment->quote->space->name ?? 'Espaço',
        ];
    }
}

