<?php

namespace Tests\Feature;

use App\Enums\ProductType;
use App\Enums\SpaceType;
use App\Enums\UnitType;
use App\Enums\UserType;
use App\Models\Company;
use App\Models\Space;
use App\Models\StorageRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MatchingEngineTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_finds_compatible_spaces_for_a_request()
    {
        $client = Company::create([
            'trade_name' => 'Cliente', 'legal_name' => 'C Ltda', 'cnpj' => '11.111.111/0001-11', 'email' => 'c@c.com', 'password' => 'Senha@123', 'phone' => '11', 'city' => 'Rio de Janeiro', 'state' => 'RJ', 'type' => UserType::Cliente,
        ]);

        $request = StorageRequest::create([
            'company_id' => $client->id,
            'title' => 'Armazenamento',
            'status' => \App\Enums\RequestStatus::Pendente,
            'product_type' => ProductType::Congelados,
            'description' => 'Carne',
            'quantity' => 100,
            'unit' => UnitType::Pallets,
            'temp_min' => -18, 'temp_max' => -10,
            'start_date' => now(), 'end_date' => now()->addDays(30),
            'target_city' => 'São Paulo',
            'target_state' => 'SP',
        ]);

        Sanctum::actingAs($client);

        $partner = Company::create([
            'trade_name' => 'Parceiro', 'legal_name' => 'P Ltda', 'cnpj' => '22.222.222/0001-22', 'email' => 'p@p.com', 'password' => 'Senha@123', 'phone' => '22', 'city' => 'São Paulo', 'state' => 'SP', 'type' => UserType::Cliente,
        ]);

        $commonData = [
            'available_pallet_positions' => 500,
            'available_from' => now(),
            'available_until' => now()->addYear(),
            'contact_name' => 'Gerente',
            'contact_email' => 'g@g.com',
            'contact_phone' => '999999999',
            'zip_code' => '000', 'address' => 'R', 'number' => '1', 'district' => 'D'
        ];

        Space::create(array_merge($commonData, [
            'company_id' => $partner->id,
            'name' => 'Câmara Perfeita',
            'city' => 'São Paulo', 'state' => 'SP',
            'type' => SpaceType::Congelado,
            'temp_min' => -30, 'temp_max' => -5,
            'capacity' => 500,
            'capacity_unit' => UnitType::Pallets,
            'active' => true,
        ]));

        Space::create(array_merge($commonData, [
            'company_id' => $partner->id,
            'name' => 'Câmara Quente',
            'city' => 'São Paulo', 'state' => 'SP',
            'type' => SpaceType::Resfriado,
            'temp_min' => 0, 'temp_max' => 10,
            'capacity' => 500,
            'capacity_unit' => UnitType::Pallets,
            'active' => true,
        ]));

        Space::create(array_merge($commonData, [
            'company_id' => $partner->id,
            'name' => 'Câmara Carioca',
            'city' => 'Rio de Janeiro', 'state' => 'RJ',
            'type' => SpaceType::Congelado,
            'temp_min' => -30, 'temp_max' => -5,
            'capacity' => 500,
            'capacity_unit' => UnitType::Pallets,
            'active' => true,
        ]));

        $response = $this->getJson("/api/storage-requests/{$request->id}/matches");

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonFragment(['name' => 'Câmara Perfeita']);
    }
}