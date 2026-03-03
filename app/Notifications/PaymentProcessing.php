<?php

namespace App\Notifications;

use App\Models\Payment;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class PaymentProcessing extends Notification
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
            'message' => 'Seu pagamento está sendo processado. Aguarde a confirmação.',
            'type' => 'payment',
            'payment_id' => $this->payment->id,
            'quote_id' => $this->payment->quote_id,
            'amount' => (float) $this->payment->amount,
            'formatted_amount' => $this->payment->formatted_amount,
            'payment_method' => $this->payment->payment_method?->value,
            'payment_method_label' => $this->payment->payment_method?->label(),
            'status' => $this->payment->status->value,
            'payment_url' => $this->payment->payment_url,
            'payment_code' => $this->payment->payment_code,
        ];
    }
}

