<?php

namespace App\Enums;

enum PaymentStatus: string
{
    case Pending = 'pending';
    case Processing = 'processing';
    case Paid = 'paid';
    case Failed = 'failed';
    case Refunded = 'refunded';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match($this) {
            self::Pending => 'Aguardando Pagamento',
            self::Processing => 'Processando',
            self::Paid => 'Pago',
            self::Failed => 'Falhou',
            self::Refunded => 'Reembolsado',
            self::Cancelled => 'Cancelado',
        };
    }

    public function color(): string
    {
        return match($this) {
            self::Pending => 'amber',
            self::Processing => 'blue',
            self::Paid => 'emerald',
            self::Failed => 'red',
            self::Refunded => 'orange',
            self::Cancelled => 'gray',
        };
    }

    public static function getOptions(): array
    {
        return array_map(
            fn($case) => ['value' => $case->value, 'label' => $case->label()],
            self::cases()
        );
    }
}

