<?php

namespace App\Notifications;

use App\Models\Space;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue; // Recomendado para não travar a req
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SpaceAnalyzedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $space;
    protected $isApproved;

    public function __construct(Space $space, bool $isApproved)
    {
        $this->space = $space;
        $this->isApproved = $isApproved;
    }

    public function via(object $notifiable): array
    {
        // Envia por e-mail e salva no banco de dados
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $status = $this->isApproved ? 'Aprovado' : 'Rejeitado';
        $color = $this->isApproved ? 'success' : 'error';

        return (new MailMessage)
            ->subject("Fica Frio: Seu espaço foi {$status}")
            ->greeting("Olá, {$notifiable->trade_name}")
            ->line("O status do seu espaço '{$this->space->name}' foi atualizado para: **{$status}**.")
            ->action('Verificar Plataforma', url(env('FRONTEND_URL', 'http://localhost:5173') . '/dashboard'))
            ->line('Obrigado por usar a Fica Frio!');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'space_id' => $this->space->id,
            'space_name' => $this->space->name,
            'status' => $this->isApproved ? 'aprovado' : 'rejeitado',
            'message' => $this->isApproved 
                ? "Seu espaço '{$this->space->name}' foi aprovado e já está visível nas buscas!" 
                : "Seu espaço '{$this->space->name}' foi rejeitado. Verifique os dados e tente novamente.",
        ];
    }
}