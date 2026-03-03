<?php

namespace App\Services;

use App\Models\Company;
use App\Models\Space;
use App\Models\StorageRequest;
use App\Enums\UserType;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DashboardService
{
    /**
     * Obtém estatísticas do dashboard para o usuário
     * 
     * @param Company $user
     * @return array
     */
    public function getUserStats(Company $user): array
    {
        $cacheKey = "dashboard_stats_user_{$user->id}";
        
        return Cache::remember($cacheKey, now()->addMinutes(5), function () use ($user) {
            return [
                'mode' => 'user',
                'totalSpaces' => $this->getTotalSpaces($user),
                'activeSpaces' => $this->getActiveSpaces($user),
                'totalRequests' => $this->getTotalRequests($user),
                'activeRequests' => $this->getActiveRequests($user),
                'totalCities' => $this->getTotalCities($user),
            ];
        });
    }

    /**
     * Obtém estatísticas do dashboard para admin
     * 
     * @return array
     */
    public function getAdminStats(): array
    {
        $cacheKey = "dashboard_stats_admin";
        
        return Cache::remember($cacheKey, now()->addMinutes(5), function () {
            return [
                'mode' => 'admin',
                'totalCompanies' => $this->getTotalCompanies(),
                'pendingSpaces' => $this->getPendingSpaces(),
                'activeSpaces' => $this->getActiveSpacesGlobal(),
                'totalRequests' => $this->getTotalRequestsGlobal(),
                'recentUsers' => $this->getRecentUsers(),
            ];
        });
    }

    /**
     * Limpa cache de estatísticas
     * 
     * @param int|null $userId
     * @return void
     */
    public function clearCache(?int $userId = null): void
    {
        if ($userId) {
            Cache::forget("dashboard_stats_user_{$userId}");
        }
        Cache::forget("dashboard_stats_admin");
    }

    // Métodos privados para queries otimizadas

    private function getTotalSpaces(Company $user): int
    {
        return Space::where('company_id', $user->id)->count();
    }

    private function getActiveSpaces(Company $user): int
    {
        return Space::where('company_id', $user->id)
            ->where('active', true)
            ->count();
    }

    private function getTotalRequests(Company $user): int
    {
        return StorageRequest::where('company_id', $user->id)->count();
    }

    private function getActiveRequests(Company $user): int
    {
        return StorageRequest::where('company_id', $user->id)
            ->where('status', \App\Enums\RequestStatus::Pendente->value)
            ->count();
    }

    private function getTotalCities(Company $user): int
    {
        return Space::where('company_id', $user->id)
            ->distinct('city')
            ->count('city');
    }

    private function getTotalCompanies(): int
    {
        return Company::count();
    }

    private function getPendingSpaces(): int
    {
        return Space::where('active', false)->count();
    }

    private function getActiveSpacesGlobal(): int
    {
        return Space::where('active', true)->count();
    }

    private function getTotalRequestsGlobal(): int
    {
        return StorageRequest::count();
    }

    private function getRecentUsers(): array
    {
        return Company::select(['trade_name', 'email', 'created_at'])
            ->latest()
            ->take(5)
            ->get()
            ->map(function ($company) {
                return [
                    'trade_name' => $company->trade_name,
                    'email' => $company->email,
                    'created_at' => $company->created_at->toIso8601String(),
                ];
            })
            ->toArray();
    }
}

