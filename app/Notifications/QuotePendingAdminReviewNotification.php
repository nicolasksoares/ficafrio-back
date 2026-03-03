<?php

namespace App\Notifications;

use App\Models\Quote;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class QuotePendingAdminReviewNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Quote $quote
    ) {}

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toArray($notifiable): array
    {
        $quote = $this->quote->loadMissing(['space.company', 'storageRequest.company']);

        return [
            'message' => 'Nova proposta aguardando aprovação',
            'status' => 'em_analise_admin',
            'quote_id' => $quote->id,
            'space_name' => $quote->space->name ?? '',
            'owner_name' => $quote->space->company->trade_name ?? '',
            'client_name' => $quote->storageRequest->company->trade_name ?? '',
            'price' => (float) $quote->price,
            'quantity' => $quote->storageRequest->quantity,
        ];
    }
}
