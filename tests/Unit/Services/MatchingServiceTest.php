<?php

namespace Tests\Unit\Services;

use App\Enums\ProductType;
use App\Enums\SpaceType;
use App\Enums\UnitType;
use App\Enums\UserType;
use App\Models\Company;
use App\Models\Space;
use App\Models\StorageRequest;
use App\Services\MatchingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MatchingServiceTest extends TestCase
{
    use RefreshDatabase;

    private MatchingService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new MatchingService;
    }

    public function test_find_matches_returns_spaces_in_same_city_and_state(): void
    {
        $client = Company::factory()->create(['type' => UserType::Cliente]);
        $partner = Company::factory()->create(['type' => UserType::Cliente]);

        $request = StorageRequest::create([
            'company_id' => $client->id,
            'title' => 'Armazenamento Teste',
            'product_type' => ProductType::Congelados->value,
            'quantity' => 100,
            'unit' => UnitType::Pallets->value,
            'temp_min' => -18,
            'temp_max' => -10,
            'start_date' => now(),
            'end_date' => now()->addDays(30),
            'target_city' => 'São Paulo',
            'target_state' => 'SP',
        ]);

        $space = Space::create([
            'company_id' => $partner->id,
            'name' => 'Câmara A',
            'zip_code' => '01310-100',
            'address' => 'Av Paulista',
            'number' => '1000',
            'district' => 'Bela Vista',
            'city' => 'São Paulo',
            'state' => 'SP',
            'type' => SpaceType::Congelado,
            'temp_min' => -20,
            'temp_max' => -5,
            'capacity' => 500,
            'available_pallet_positions' => 500,
            'available_from' => now(),
            'available_until' => now()->addYear(),
            'contact_name' => 'Gerente',
            'contact_email' => 'gerente@test.com',
            'contact_phone' => '11999999999',
            'active' => true,
        ]);

        $matches = $this->service->findMatches($request);

        $this->assertCount(1, $matches);
        $this->assertEquals($space->id, $matches->first()->id);
    }

    public function test_find_matches_excludes_spaces_in_different_city(): void
    {
        $client = Company::factory()->create();
        $partner = Company::factory()->create();

        $request = StorageRequest::create([
            'company_id' => $client->id,
            'title' => 'Armazenamento Teste',
            'product_type' => ProductType::Congelados->value,
            'quantity' => 100,
            'unit' => UnitType::Pallets->value,
            'temp_min' => -18,
            'temp_max' => -10,
            'start_date' => now(),
            'end_date' => now()->addDays(30),
            'target_city' => 'São Paulo',
            'target_state' => 'SP',
        ]);

        Space::create([
            'company_id' => $partner->id,
            'name' => 'Câmara Rio',
            'zip_code' => '20000',
            'address' => 'Av Rio',
            'number' => '1',
            'district' => 'Centro',
            'city' => 'Rio de Janeiro',
            'state' => 'RJ',
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

        $matches = $this->service->findMatches($request);

        $this->assertCount(0, $matches);
    }

    public function test_find_matches_excludes_inactive_spaces(): void
    {
        $client = Company::factory()->create();
        $partner = Company::factory()->create();

        $request = StorageRequest::create([
            'company_id' => $client->id,
            'title' => 'Armazenamento Teste',
            'product_type' => ProductType::Congelados->value,
            'quantity' => 100,
            'unit' => UnitType::Pallets->value,
            'temp_min' => -18,
            'temp_max' => -10,
            'start_date' => now(),
            'end_date' => now()->addDays(30),
            'target_city' => 'São Paulo',
            'target_state' => 'SP',
        ]);

        Space::create([
            'company_id' => $partner->id,
            'name' => 'Câmara Inativa',
            'zip_code' => '01310',
            'address' => 'Av',
            'number' => '1',
            'district' => 'D',
            'city' => 'São Paulo',
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
            'active' => false,
        ]);

        $matches = $this->service->findMatches($request);

        $this->assertCount(0, $matches);
    }

    public function test_find_matches_requires_sufficient_capacity(): void
    {
        $client = Company::factory()->create();
        $partner = Company::factory()->create();

        $request = StorageRequest::create([
            'company_id' => $client->id,
            'title' => 'Armazenamento Teste',
            'product_type' => ProductType::Congelados->value,
            'quantity' => 1000,
            'unit' => UnitType::Pallets->value,
            'temp_min' => -18,
            'temp_max' => -10,
            'start_date' => now(),
            'end_date' => now()->addDays(30),
            'target_city' => 'São Paulo',
            'target_state' => 'SP',
        ]);

        Space::create([
            'company_id' => $partner->id,
            'name' => 'Câmara Pequena',
            'zip_code' => '01310',
            'address' => 'Av',
            'number' => '1',
            'district' => 'D',
            'city' => 'São Paulo',
            'state' => 'SP',
            'type' => SpaceType::Congelado,
            'temp_min' => -20,
            'temp_max' => -5,
            'capacity' => 50,
            'capacity_unit' => UnitType::Pallets->value,
            'available_pallet_positions' => 50,
            'available_from' => now(),
            'available_until' => now()->addYear(),
            'contact_name' => 'G',
            'contact_email' => 'g@g.com',
            'contact_phone' => '11',
            'active' => true,
        ]);

        $matches = $this->service->findMatches($request);

        $this->assertCount(0, $matches);
    }
}
