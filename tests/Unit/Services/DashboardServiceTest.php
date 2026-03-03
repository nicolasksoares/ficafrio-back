<?php

namespace Tests\Unit\Services;

use App\Enums\ProductType;
use App\Enums\RequestStatus;
use App\Enums\SpaceType;
use App\Enums\UnitType;
use App\Enums\UserType;
use App\Models\Company;
use App\Models\Space;
use App\Models\StorageRequest;
use App\Services\DashboardService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class DashboardServiceTest extends TestCase
{
    use RefreshDatabase;

    private DashboardService $service;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
        $this->service = new DashboardService;
    }

    public function test_get_user_stats_returns_correct_counts(): void
    {
        $company = Company::factory()->create(['type' => UserType::Cliente]);

        Space::create([
            'company_id' => $company->id,
            'name' => 'Câmara 1',
            'zip_code' => '000',
            'address' => 'R',
            'number' => '1',
            'district' => 'D',
            'city' => 'SP',
            'state' => 'SP',
            'type' => SpaceType::Congelado,
            'temp_min' => -20,
            'temp_max' => -5,
            'capacity' => 500,
            'capacity_unit' => UnitType::Pallets->value,
            'available_pallet_positions' => 500,
            'available_from' => now(),
            'available_until' => now()->addYear(),
            'contact_name' => 'G',
            'contact_email' => 'g@g.com',
            'contact_phone' => '11',
            'active' => true,
        ]);

        Space::create([
            'company_id' => $company->id,
            'name' => 'Câmara 2',
            'zip_code' => '000',
            'address' => 'R',
            'number' => '1',
            'district' => 'D',
            'city' => 'RJ',
            'state' => 'RJ',
            'type' => SpaceType::Congelado,
            'temp_min' => -20,
            'temp_max' => -5,
            'capacity' => 300,
            'capacity_unit' => UnitType::Pallets->value,
            'available_pallet_positions' => 300,
            'available_from' => now(),
            'available_until' => now()->addYear(),
            'contact_name' => 'G',
            'contact_email' => 'g@g.com',
            'contact_phone' => '11',
            'active' => false,
        ]);

        StorageRequest::create([
            'company_id' => $company->id,
            'title' => 'Armazenamento Teste',
            'product_type' => ProductType::Congelados->value,
            'quantity' => 50,
            'unit' => UnitType::Pallets->value,
            'temp_min' => -18,
            'temp_max' => -10,
            'start_date' => now(),
            'end_date' => now()->addDays(30),
            'target_city' => 'SP',
            'target_state' => 'SP',
            'status' => RequestStatus::Pendente->value,
        ]);

        $stats = $this->service->getUserStats($company);

        $this->assertEquals('user', $stats['mode']);
        $this->assertEquals(2, $stats['totalSpaces']);
        $this->assertEquals(1, $stats['activeSpaces']);
        $this->assertEquals(1, $stats['totalRequests']);
        $this->assertEquals(1, $stats['activeRequests']);
        $this->assertEquals(2, $stats['totalCities']);
    }

    public function test_get_admin_stats_returns_correct_counts(): void
    {
        $companies = Company::factory()->count(3)->create();
        $partner = Company::factory()->create();
        Space::create([
            'company_id' => $partner->id,
            'name' => 'A',
            'zip_code' => '000',
            'address' => 'R',
            'number' => '1',
            'district' => 'D',
            'city' => 'SP',
            'state' => 'SP',
            'type' => SpaceType::Congelado,
            'temp_min' => -20,
            'temp_max' => -5,
            'capacity' => 500,
            'capacity_unit' => UnitType::Pallets->value,
            'available_pallet_positions' => 500,
            'available_from' => now(),
            'available_until' => now()->addYear(),
            'contact_name' => 'G',
            'contact_email' => 'g@g.com',
            'contact_phone' => '11',
            'active' => true,
        ]);

        Space::create([
            'company_id' => $partner->id,
            'name' => 'B',
            'zip_code' => '000',
            'address' => 'R',
            'number' => '1',
            'district' => 'D',
            'city' => 'SP',
            'state' => 'SP',
            'type' => SpaceType::Congelado,
            'temp_min' => -20,
            'temp_max' => -5,
            'capacity' => 300,
            'capacity_unit' => UnitType::Pallets->value,
            'available_pallet_positions' => 300,
            'available_from' => now(),
            'available_until' => now()->addYear(),
            'contact_name' => 'G',
            'contact_email' => 'g@g.com',
            'contact_phone' => '11',
            'active' => false,
        ]);

        StorageRequest::create([
            'company_id' => $companies->first()->id,
            'title' => 'Armazenamento Teste',
            'product_type' => ProductType::Congelados->value,
            'quantity' => 50,
            'unit' => UnitType::Pallets->value,
            'temp_min' => -18,
            'temp_max' => -10,
            'start_date' => now(),
            'end_date' => now()->addDays(30),
            'target_city' => 'SP',
            'target_state' => 'SP',
        ]);

        $stats = $this->service->getAdminStats();

        $this->assertEquals('admin', $stats['mode']);
        $this->assertEquals(4, $stats['totalCompanies']);
        $this->assertEquals(1, $stats['pendingSpaces']);
        $this->assertEquals(1, $stats['activeSpaces']);
        $this->assertEquals(1, $stats['totalRequests']);
        $this->assertIsArray($stats['recentUsers']);
        $this->assertLessThanOrEqual(5, count($stats['recentUsers']));
    }

    public function test_clear_cache_removes_user_and_admin_cache(): void
    {
        $company = Company::factory()->create();
        $this->service->getUserStats($company);
        $this->service->getAdminStats();

        $this->assertTrue(Cache::has('dashboard_stats_user_' . $company->id));
        $this->assertTrue(Cache::has('dashboard_stats_admin'));

        $this->service->clearCache($company->id);
        $this->assertFalse(Cache::has('dashboard_stats_user_' . $company->id));
        $this->assertFalse(Cache::has('dashboard_stats_admin'));
    }
}
