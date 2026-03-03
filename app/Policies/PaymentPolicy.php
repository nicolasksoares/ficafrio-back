<?php

namespace App\Policies;

use App\Models\Company;
use App\Models\Payment;
use App\Enums\UserType;

class PaymentPolicy
{
    /**
     * Determina se o usuário pode visualizar o pagamento
     */
    public function view(Company $user, Payment $payment): bool
    {
        // Usuário pode ver se é quem paga ou quem recebe
        return $user->id === $payment->company_id || 
               $user->id === $payment->space_owner_id ||
               $user->type === UserType::Admin;
    }

    /**
     * Determina se o usuário pode criar pagamento
     */
    public function create(Company $user, Payment $payment = null): bool
    {
        // Apenas quem solicitou a Quote pode criar pagamento
        // Validação específica feita no controller com a Quote
        return true;
    }

    /**
     * Determina se o usuário pode processar o pagamento
     */
    public function process(Company $user, Payment $payment): bool
    {
        // Apenas quem deve pagar pode processar
        return $user->id === $payment->company_id;
    }

    /**
     * Determina se o usuário pode reembolsar o pagamento
     */
    public function refund(Company $user, Payment $payment): bool
    {
        // Apenas admin pode reembolsar
        return $user->type === UserType::Admin;
    }

    /**
     * Determina se o usuário pode transferir o pagamento
     */
    public function transfer(Company $user, Payment $payment): bool
    {
        // Apenas admin pode forçar transferência
        return $user->type === UserType::Admin;
    }

    /**
     * Determina se o usuário pode ver estatísticas
     */
    public function viewStats(Company $user): bool
    {
        // Apenas admin pode ver estatísticas
        return $user->type === UserType::Admin;
    }
}

