<?php

namespace Tests\Feature;

use App\Enums\SpaceStatus;
use App\Enums\SpaceType;
use App\Enums\UserType;
use App\Models\Company;
use App\Models\Space;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SpaceManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_company_can_create_a_cold_storage_space()
    {
        $company = Company::create([
            'trade_name' => 'Frigorífico Sul', 'legal_name' => 'Frigorífico Sul Ltda',
            'cnpj' => '12.345.678/0001-00', 'email' => 'frigo@teste.com',
            'password' => bcrypt('Senha@123'), 'phone' => '11999999999',
            'city' => 'São Paulo', 'state' => 'SP',
            'type' => UserType::Cliente, 
            'address_street' => 'Rua Sede', 
        ]);

        Sanctum::actingAs($company);

        $payload = [
            'name' => 'Câmara 01 - Congelados',
            'description' => 'Espaço ideal para carnes nobres',
            'zip_code' => '01001-000',
            'street_address' => 'Rua do Frio', 
            'number' => '500',
            'district' => 'Industrial',
            'city' => 'São Paulo',
            'state' => 'SP',
            'min_temperature_celsius' => -20, 
            'max_temperature_celsius' => -10, 
            'total_pallet_positions' => 1000, 
            'available_pallet_positions' => 1000,
            'contact_name' => 'Gerente de Teste',
            'contact_email' => 'contato@camara.com',
            'contact_phone' => '11999999999',
            'type' => SpaceType::Congelado->value, 
            'has_anvisa' => true, 'has_generator' => true, 'has_security' => true, 'active' => true,
        ];

        $response = $this->postJson('/api/spaces', $payload);

        $response->assertStatus(201);

        $this->assertDatabaseHas('spaces', [
            'name' => 'Câmara 01 - Congelados',
            'company_id' => $company->id,
            'temp_min' => -20, 
            'type' => SpaceType::Congelado->value,
        ]);
    }

    public function test_company_cannot_update_space_from_another_company()
    {
        $hacker = Company::create([
            'trade_name' => 'Hacker', 'legal_name' => 'Hacker Ltda',
            'cnpj' => '11.111.111/0001-11', 'email' => 'h@h.com',
            'password' => bcrypt('Senha@123'), 'phone' => '119999999', 'city' => 'SP', 'state' => 'SP',
            'type' => UserType::Cliente, 'address_street' => 'Rua Mal',
        ]);

        $victim = Company::create([
            'trade_name' => 'Vitima', 'legal_name' => 'Vitima Ltda',
            'cnpj' => '22.222.222/0001-22', 'email' => 'v@v.com',
            'password' => bcrypt('Senha@123'), 'phone' => '119999999', 'city' => 'SP', 'state' => 'SP',
            'type' => UserType::Cliente, 'address_street' => 'Rua Boa',
        ]);

        $space = Space::create([
            'company_id' => $victim->id,
            'name' => 'Câmara da Vítima',
            'zip_code' => '00000-000', 
            'address' => 'Rua A',
            'number' => '1', 'district' => 'B', 'city' => 'SP', 'state' => 'SP',
            'temp_min' => 0,
            'temp_max' => 5,
            'capacity' => 100,
            'type' => SpaceType::Resfriado,
            'available_pallet_positions' => 100,
            'available_from' => now(),
            'available_until' => now()->addYear(),
            'contact_name' => 'Vítima Silva',
            'contact_email' => 'vitima@teste.com',
            'contact_phone' => '11999999999',
        ]);

        Sanctum::actingAs($hacker);

        $response = $this->putJson("/api/spaces/{$space->id}", [
            'name' => 'HACKED',
            'min_temperature_celsius' => -50,
        ]);

        $response->assertStatus(403);
    }

    public function test_it_validates_required_technical_specs()
    {
        $company = Company::create([
            'trade_name' => 'Teste', 'legal_name' => 'Teste Ltda',
            'cnpj' => '33.333.333/0001-33', 'email' => 't@t.com',
            'password' => bcrypt('Senha@123'), 'phone' => '119999999', 'city' => 'SP', 'state' => 'SP',
            'type' => UserType::Cliente, 'address_street' => 'Rua T',
        ]);

        Sanctum::actingAs($company);

        $payload = ['name' => 'Espaço Incompleto'];

        $this->postJson('/api/spaces', $payload)
            ->assertStatus(422)
            ->assertJsonValidationErrors(['temp_min', 'capacity', 'city', 'contact_name']); 
    }

    public function test_company_cannot_create_duplicate_space_names()
    {
        $company = Company::create([
            'trade_name' => 'Duplica Corp', 'legal_name' => 'Duplica Ltda',
            'cnpj' => '44.444.444/0001-44', 'email' => 'dup@teste.com',
            'password' => bcrypt('Senha@123'), 'phone' => '119999999', 'city' => 'SP', 'state' => 'SP',
            'type' => UserType::Cliente,
        ]);

        Sanctum::actingAs($company);

        Space::create([
            'company_id' => $company->id,
            'name' => 'Câmara Principal',
            'zip_code' => '000', 
            'address' => 'Rua', 
            'number' => '1', 'district' => 'B', 'city' => 'SP', 'state' => 'SP',
            'temp_min' => -10, 
            'temp_max' => 0,   
            'capacity' => 100, 
            'type' => SpaceType::Resfriado,
            'available_pallet_positions' => 100,
            'available_from' => now(),
            'available_until' => now()->addYear(),
            'contact_name' => 'João Duplicado',
            'contact_email' => 'joao@teste.com',
            'contact_phone' => '11999999999',
        ]);

        $payload = [
            'name' => 'Câmara Principal',
            'zip_code' => '000', 'street_address' => 'Rua', 'number' => '1', 'district' => 'B', 'city' => 'SP', 'state' => 'SP',
            'min_temperature_celsius' => -20, 'max_temperature_celsius' => -10,
            'total_pallet_positions' => 500, 'available_pallet_positions' => 500,
            'contact_name' => 'Tentativa Falha', 
            'contact_email' => 'e@e.com', 
            'contact_phone' => '123',
            'type' => SpaceType::Congelado->value,
            'has_anvisa' => true,
        ];

        $this->postJson('/api/spaces', $payload)
            ->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    public function test_can_filter_spaces_by_type()
    {
        $company = Company::create([
            'trade_name' => 'Filtro Corp', 'legal_name' => 'Filtro Ltda',
            'cnpj' => '55.555.555/0001-55', 'email' => 'filtro@teste.com',
            'password' => bcrypt('Senha@123'), 'phone' => '119999999', 'city' => 'SP', 'state' => 'SP',
            'type' => UserType::Cliente,
        ]);

        Sanctum::actingAs($company);

        $baseData = [
            'company_id' => $company->id, 'zip_code' => '0', 
            'address' => 'R', 
            'number' => '1', 'district' => 'D',
            'city' => 'C', 'state' => 'SP', 
            'temp_min' => -10, 
            'temp_max' => 0,   
            'capacity' => 100,
            'available_pallet_positions' => 100,
            'available_from' => now(),
            'available_until' => now()->addYear(),
            'contact_name' => 'Filtro Man',
            'contact_email' => 'filtro@teste.com',
            'contact_phone' => '11999999999',
            'active' => true,
            'status' => SpaceStatus::Aprovado,
        ];

        Space::create(array_merge($baseData, ['name' => 'C1', 'type' => SpaceType::Congelado]));
        Space::create(array_merge($baseData, ['name' => 'C2', 'type' => SpaceType::Congelado]));
        Space::create(array_merge($baseData, ['name' => 'C3', 'type' => SpaceType::Resfriado]));

        $response = $this->getJson('/api/spaces?type='.SpaceType::Resfriado->value);

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }
}