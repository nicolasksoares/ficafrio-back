<?php

namespace App\Enums;

enum PaymentMethod: string
{
    case Pix = 'pix';
    case CreditCard = 'credit_card';
    case Boleto = 'boleto';

    public function label(): string
    {
        return match($this) {
            self::Pix => 'PIX',
            self::CreditCard => 'Cartão de Crédito',
            self::Boleto => 'Boleto Bancário',
        };
    }

    public function description(): string
    {
        return match($this) {
            self::Pix => 'Pagamento instantâneo via PIX',
            self::CreditCard => 'Pagamento com cartão de crédito',
            self::Boleto => 'Pagamento via boleto bancário',
        };
    }

    public function expirationDays(): int
    {
        return match($this) {
            self::Pix => 1,
            self::CreditCard => 0, // Imediato
            self::Boleto => 7,
        };
    }

    public static function getOptions(): array
    {
        return array_map(
            fn($case) => [
                'value' => $case->value,
                'label' => $case->label(),
                'description' => $case->description(),
            ],
            self::cases()
        );
    }
}

