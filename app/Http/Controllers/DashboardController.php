<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\DashboardService;
use App\Enums\UserType;
use Illuminate\Support\Facades\Log;

class DashboardController extends Controller
{
    public function __construct(
        private DashboardService $dashboardService
    ) {}

    /**
     * Retorna estatísticas do dashboard
     * 
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function stats(Request $request)
    {
        try {
            /** @var \App\Models\Company $user */
            $user = $request->user();

            if ($user->type === UserType::Admin) {
                $stats = $this->dashboardService->getAdminStats();
            } else {
                $stats = $this->dashboardService->getUserStats($user);
            }

            return response()->json($stats);
        } catch (\Exception $e) {
            Log::error('Dashboard stats error', [
                'user_id' => $request->user()->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'message' => 'Erro ao carregar estatísticas do dashboard',
                'error' => config('app.debug') ? $e->getMessage() : 'Erro interno'
            ], 500);
        }
    }
}