<?php

namespace App\Http\Controllers\Admin; // Note o namespace Admin

use App\Http\Controllers\Controller;
use App\Models\Space;
use App\Models\Company;
use App\Models\Quote;
use App\Enums\QuoteStatus;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    // Este método alimenta EXCLUSIVAMENTE o painel "Relatórios Gerais"
    public function stats()
    {
        // 1. KPIs (Indicadores Chave)
        $totalSpaces = Space::count();
        $activeSpaces = Space::where('status', 'aprovado')->where('active', true)->count();
        $pendingSpaces = Space::where('status', 'em_analise')->count();
        
        // Novos Parceiros (Últimos 30 dias)
        $newPartners = Company::where('type', 'cliente')
            ->where('created_at', '>=', Carbon::now()->subDays(30))
            ->count();

        // Cotações aguardando aprovação admin
        $pendingQuotes = Quote::where('status', QuoteStatus::EmAnaliseAdmin)->count();

        // 2. Top Cidades (Agrupamento SQL)
        $topCities = Space::select('city', 'state', DB::raw('count(*) as total'))
            ->groupBy('city', 'state')
            ->orderByDesc('total')
            ->limit(5)
            ->get()
            ->map(function ($item) {
                return [
                    'name' => $item->city . ', ' . $item->state,
                    'count' => $item->total,
                    // Simulação de tendência (no futuro você pode calcular real comparando com mês anterior)
                    'trend' => '+5%' 
                ];
            });

        // 3. Atividade Recente (Últimos espaços criados)
        $recentActivity = Space::with('company')
            ->latest()
            ->limit(5)
            ->get()
            ->map(function ($space) {
                return [
                    'id' => $space->id,
                    'text' => "Novo espaço: {$space->name}",
                    'subtext' => $space->company->trade_name ?? 'Empresa desconhecida',
                    'time' => $space->created_at->diffForHumans(), // Ex: "há 2 horas"
                    'status' => $space->status
                ];
            });

        // Estrutura exata que o Frontend (AdminIntermediationPanel) espera
        return response()->json([
            'kpis' => [
                'total_spaces' => $totalSpaces,
                'active_spaces' => $activeSpaces,
                'pending_spaces' => $pendingSpaces,
                'pending_quotes' => $pendingQuotes,
                'new_partners' => $newPartners,
                'occupancy_rate' => $totalSpaces > 0 ? round(($activeSpaces / $totalSpaces) * 100) : 0
            ],
            'top_cities' => $topCities,
            'recent_activity' => $recentActivity
        ]);
    }
}