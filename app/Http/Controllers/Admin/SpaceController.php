<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Space;
use App\Models\SpaceAudit; // <--- Novo
use App\Enums\SpaceStatus;
use App\Notifications\SpaceAnalyzedNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SpaceController extends Controller
{
    public function index()
    {
        // Query Otimizada com filtros via Backend (opcional, mas bom ter)
        $spaces = Space::with('company')
            ->orderByRaw("CASE WHEN status = 'em_analise' THEN 0 ELSE 1 END ASC")
            ->latest()
            ->paginate(50); // Aumentei para 50 para facilitar a busca no front

        return response()->json($spaces);
    }

    public function analyze(Request $request, $id)
    {
        $request->validate([
            'approved' => 'required|boolean',
            'reason'   => 'nullable|string'
        ]);

        $space = Space::findOrFail($id);
        $oldStatus = $space->status;
        $approved = $request->boolean('approved');
        $newStatus = $approved ? SpaceStatus::Aprovado : SpaceStatus::Rejeitado;

        DB::transaction(function () use ($space, $approved, $newStatus, $oldStatus, $request) {
            // 1. Atualiza Espaço
            $space->status = $newStatus;
            $space->active = $approved;
            $space->save();

            // 2. Cria Auditoria
            SpaceAudit::create([
                'space_id' => $space->id,
                'admin_id' => $request->user()->id,
                'old_status' => $oldStatus,
                'new_status' => $newStatus,
                'reason' => $request->reason
            ]);
        });

        // 3. Notifica (Fora da transação para não travar)
        try {
            if ($space->company) {
                $space->company->notify(new SpaceAnalyzedNotification($space, $approved, $request->reason));
                
                // AQUI ENTRARIA O DISPARO DE E-MAIL:
                // Mail::to($space->company->email)->send(new SpaceStatusChangedMail($space));
            }
        } catch (\Exception $e) {
            // Log::error("Erro ao notificar: " . $e->getMessage());
        }

        return response()->json(['message' => 'Processado com sucesso', 'data' => $space]);
    }
}