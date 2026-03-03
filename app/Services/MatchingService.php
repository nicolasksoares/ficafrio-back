<?php

namespace App\Services;

use App\Models\Space;
use App\Models\StorageRequest;
use Illuminate\Database\Eloquent\Builder;

class MatchingService
{
    public function findMatches(StorageRequest $request)
    {
        return Space::query()
            ->where('active', true)

            // 1. CORREÇÃO: Filtra pelo local desejado, não pela sede
            ->where('city', $request->target_city)
            ->where('state', $request->target_state)

            // 2. CORREÇÃO: Garante mesma unidade de medida para a comparação fazer sentido
            ->where('capacity_unit', $request->unit)

            // 3. Capacidade
            ->where('capacity', '>=', $request->quantity)

            // 4. Temperatura
            ->where(function (Builder $query) use ($request) {
                $query->where('temp_min', '<=', $request->temp_min)
                    ->where('temp_max', '>=', $request->temp_max);
            })

            ->latest()
            ->paginate(10);
    }
}
