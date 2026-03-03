<?php

namespace App\Policies;

use App\Enums\UserType;
use App\Models\Company;
use App\Models\Space;

class SpacePolicy
{
    public function create(Company $company): bool
    {
        return $company->type === UserType::Cliente;
    }

    public function update(Company $company, Space $space): bool
    {
        return $company->id === $space->company_id;
    }

    public function delete(Company $company, Space $space): bool
    {
        return $company->id === $space->company_id;
    }
}
