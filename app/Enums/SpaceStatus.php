<?php

namespace App\Enums;

enum SpaceStatus: string
{
    case EmAnalise = 'em_analise';
    case Aprovado = 'aprovado';
    case Rejeitado = 'rejeitado';

    public function label(): string
    {
        return match($this) {
            self::EmAnalise => 'Em Análise',
            self::Aprovado => 'Aprovado',
            self::Rejeitado => 'Rejeitado',
        };
    }
}