<?php

namespace App\Enums;

enum QuoteStatus: string
{
    case Solicitado = 'solicitado';       // Cliente pediu preço
    case EmAnaliseAdmin = 'em_analise_admin'; // Parceiro enviou preço; aguarda aprovação admin
    case Respondido = 'respondido';       // Admin aprovou; cliente pode aceitar
    case Aceito = 'aceito';               // Cliente aceitou
    case Rejeitado = 'rejeitado';         // Admin ou parceiro recusou
}
