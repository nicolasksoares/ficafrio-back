<?php

namespace App\Policies;

use App\Enums\UserType;
use App\Models\Company;
use App\Models\Quote;

class QuotePolicy
{
    public function create(Company $user): bool
    {
        return $user->type === UserType::Cliente;
    }

    public function update(Company $user, Quote $quote): bool
    {
        // Se o usuário for o dono da solicitação OU o dono do espaço, ele tem acesso
        return $user->id === $quote->storageRequest->company_id || 
               $user->id === $quote->space->company_id;
    }

    public function view(Company $user, Quote $quote): bool
    {
        return $this->update($user, $quote);
    }
}