<?php

namespace App\Policies;

use App\Enums\UserType;
use App\Models\Company;
use App\Models\StorageRequest;

class StorageRequestPolicy
{
    // Só CLIENTES podem criar solicitações
    public function create(Company $company): bool
    {
        return $company->type === UserType::Cliente;
    }

    // Só o DONO pode ver/deletar
    public function delete(Company $company, StorageRequest $storageRequest): bool
    {
        return $company->id === $storageRequest->company_id;
    }
}
