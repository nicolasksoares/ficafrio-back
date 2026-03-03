<?php

namespace App\Notifications;

use App\Models\Payment;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class PaymentFailed extends Notification
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
            'message' => 'O pagamento falhou. Tente novamente ou escolha outro método.',
            'type' => 'payment',
            'payment_id' => $this->payment->id,
            'quote_id' => $this->payment->quote_id,
            'amount' => (float) $this->payment->amount,
            'formatted_amount' => $this->payment->formatted_amount,
            'status' => $this->payment->status->value,
            'space_name' => $this->payment->quote->space->name ?? 'Espaço',
        ];
    }
}

