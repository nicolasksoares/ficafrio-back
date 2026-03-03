<?php

namespace App\Services;

use App\Models\Space;
use App\Models\Quote;
use App\Enums\QuoteStatus;
use Carbon\Carbon;

class OccupationService
{
    public function hasSpaceAvailable(Space $space, $quantity, $startDate, $endDate): bool
    {
        // 1. Pega todas as propostas ACEITAS que sobrepõem o período desejado
        $occupied = Quote::where('space_id', $space->id)
            ->where('status', QuoteStatus::Aceito)
            ->whereHas('storageRequest', function ($q) use ($startDate, $endDate) {
                $q->where(function ($query) use ($startDate, $endDate) {
                    $query->whereBetween('start_date', [$startDate, $endDate])
                          ->orWhereBetween('end_date', [$startDate, $endDate])
                          ->orWhere(function ($sub) use ($startDate, $endDate) {
                              $sub->where('start_date', '<=', $startDate)
                                  ->where('end_date', '>=', $endDate);
                          });
                });
            })
            ->with('storageRequest')
            ->get()
            ->sum('storage_request.quantity');

        // 2. Verifica se a capacidade total menos o que já está reservado aguenta o novo pedido
        return ($space->capacity - $occupied) >= $quantity;
    }
}