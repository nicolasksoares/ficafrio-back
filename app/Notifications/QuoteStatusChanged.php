<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use App\Models\Quote;

class QuoteStatusChanged extends Notification
{
    use Queueable;

    public function __construct(
        public string $message, 
        public string $status,
        public Quote $quote
    ) {}

    public function via($notifiable): array
    {
        return ['database'];
    }

    public function toArray($notifiable): array
    {
        return [
            'message' => $this->message,
            'status' => $this->status,
            'quote_id' => $this->quote->id,
            'space_name' => $this->quote->space->name,
            'owner_name' => $this->quote->space->company->trade_name,
            'price' => (float) $this->quote->price,
            'quantity' => $this->quote->storageRequest->quantity,
        ];
    }
}