<?php

namespace App\Enums;

// O erro estava aqui: estava escrito "enum UnitType" ao invés de "enum RequestStatus"
enum RequestStatus: string
{
    case Pendente = 'pendente';
    case Respondido = 'respondido';
    case Aprovado = 'aprovado';
    case Rejeitado = 'rejeitado';
    case Cancelado = 'cancelado';

    public function label(): string
    {
        return match($this) {
            self::Pendente => 'Pendente',
            self::Respondido => 'Respondido',
            self::Aprovado => 'Aprovado',
            self::Rejeitado => 'Rejeitado',
            self::Cancelado => 'Cancelado',
        };
    }
}