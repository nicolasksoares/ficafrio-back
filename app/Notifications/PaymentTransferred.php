<?php

namespace App\Notifications;

use App\Models\Payment;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class PaymentTransferred extends Notification
{
    use Queueable;

    public function __construct(public Payment $payment) {}

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toArray($notifiable): array
    {
        return [
            'message' => "Transferência recebida! Você recebeu {$this->payment->formatted_net_amount} pela locação do espaço.",
            'type' => 'payment',
            'payment_id' => $this->payment->id,
            'quote_id' => $this->payment->quote_id,
            'net_amount' => (float) $this->payment->net_amount,
            'formatted_net_amount' => $this->payment->formatted_net_amount,
            'platform_fee' => (float) $this->payment->platform_fee,
            'formatted_fee' => $this->payment->formatted_fee,
            'status' => $this->payment->status->value,
            'space_name' => $this->payment->quote->space->name ?? 'Espaço',
        ];
    }
}

