<?php

namespace Tests\Feature;

use App\Enums\ProductType;
use App\Enums\RequestStatus;
use App\Enums\UnitType;
use App\Enums\UserType;
use App\Models\Company;
use App\Models\StorageRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum; 
use Tests\TestCase;

class StorageRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_client_can_create_storage_request()
    {
        $client = Company::create([
            'trade_name' => 'Cliente Alimentos', 'legal_name' => 'Cliente Ltda',
            'cnpj' => '12.345.678/0001-95', 'email' => 'client@teste.com',
            'password' => 'Senha@123', 'phone' => '119999999', 'city' => 'SP', 'state' => 'SP',
            'type' => UserType::Cliente,
        ]);

        Sanctum::actingAs($client);

        $payload = [
            'product_type' => ProductType::Congelados->value,
            'description' => '50 Toneladas de Carne Bovina',
            'quantity' => 50,
            'unit' => UnitType::Toneladas->value,
            'temp_min' => -20,
            'temp_max' => -10,
            'start_date' => now()->addDays(5)->format('Y-m-d'),
            'end_date' => now()->addDays(35)->format('Y-m-d'),
            'status' => 'aprovado', // Hack attempt
            'target_city' => 'São Paulo',
            'target_state' => 'SP',
        ];

        $response = $this->postJson('/api/storage-requests', $payload);

        $response->assertStatus(201);

        $this->assertDatabaseHas('storage_requests', [
            'company_id' => $client->id,
            'description' => '50 Toneladas de Carne Bovina',
            'status' => RequestStatus::Pendente->value,
        ]);
    }

    public function test_client_can_update_pending_request()
    {
        $client = Company::create([
            'trade_name' => 'Cliente Update', 'legal_name' => 'Update Ltda',
            'cnpj' => '12.345.678/0001-95', 'email' => 'up@teste.com',
            'password' => 'Senha@123', 'phone' => '11999', 'city' => 'SP', 'state' => 'SP', 'type' => UserType::Cliente,
        ]);

        Sanctum::actingAs($client);

        $request = StorageRequest::create([
            'company_id' => $client->id,
            'title' => 'Armazenamento',
            'product_type' => ProductType::Congelados,
            'quantity' => 50, 'unit' => UnitType::Kg, 'temp_min' => -20, 'temp_max' => -10,
            'start_date' => now(), 'end_date' => now()->addDays(10),
            'status' => RequestStatus::Pendente,
        ]);

        $response = $this->putJson("/api/storage-requests/{$request->id}", [
            'quantity' => 100, 
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('storage_requests', ['id' => $request->id, 'quantity' => 100]);
    }

    public function test_it_validates_end_date_must_be_after_start_date()
    {
        $client = Company::create([
            'trade_name' => 'Cliente Datas', 'legal_name' => 'Datas Ltda',
            'cnpj' => '12.345.678/0001-95', 'email' => 'datas@teste.com',
            'password' => 'Senha@123', 'phone' => '119999999', 'city' => 'SP', 'state' => 'SP',
            'type' => UserType::Cliente,
        ]);

        Sanctum::actingAs($client);

        $payload = [
            'product_type' => ProductType::Congelados->value,
            'quantity' => 10, 'unit' => UnitType::Kg->value, 'temp_min' => 0, 'temp_max' => 5,
            'start_date' => '2025-12-30',
            'end_date' => '2025-12-01',
        ];

        $this->postJson('/api/storage-requests', $payload)
            ->assertStatus(422)
            ->assertJsonValidationErrors(['end_date']);
    }

    // REMOVIDO test_partner_cannot_create_storage_request POIS AGORA PODE

    public function test_client_cannot_delete_another_client_request()
    {
        $hacker = Company::create([
            'trade_name' => 'Hacker', 'legal_name' => 'Hacker Ltda',
            'cnpj' => '11.111.111/0001-11', 'email' => 'h@h.com',
            'password' => 'Senha@123', 'phone' => '1199', 'city' => 'SP', 'state' => 'SP',
            'type' => UserType::Cliente,
        ]);

        $victim = Company::create([
            'trade_name' => 'Vitima', 'legal_name' => 'Vitima Ltda',
            'cnpj' => '22.222.222/0001-22', 'email' => 'v@v.com',
            'password' => 'Senha@123', 'phone' => '1199', 'city' => 'SP', 'state' => 'SP',
            'type' => UserType::Cliente,
        ]);

        $request = StorageRequest::create([
            'company_id' => $victim->id,
            'title' => 'Armazenamento Químicos',
            'product_type' => ProductType::Quimicos,
            'quantity' => 100, 'unit' => UnitType::Kg, 'temp_min' => 10, 'temp_max' => 25,
            'start_date' => now(), 'end_date' => now()->addDays(10),
            'target_city' => 'São Paulo',
            'target_state' => 'SP',
        ]);

        Sanctum::actingAs($hacker);

        $this->deleteJson("/api/storage-requests/{$request->id}")
            ->assertStatus(403);
    }
}