<?php

namespace Tests\Feature;

use App\Enums\ProductType;
use App\Enums\QuoteStatus;
use App\Enums\SpaceType;
use App\Enums\UnitType;
use App\Enums\UserType;
use App\Models\Company;
use App\Models\Quote;
use App\Models\Space;
use App\Models\StorageRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class QuoteNegotiationTest extends TestCase
{
    use RefreshDatabase;

    private function createScenario()
    {
        $client = Company::create([
            'trade_name' => 'Cliente', 'legal_name' => 'C Ltda', 'cnpj' => '11.111.111/0001-11', 'email' => 'c@c.com', 'password' => 'Senha@123', 'phone' => '11', 'city' => 'SP', 'state' => 'SP', 'type' => UserType::Cliente,
        ]);

        $partner = Company::create([
            'trade_name' => 'Parceiro', 'legal_name' => 'P Ltda', 'cnpj' => '22.222.222/0001-22', 'email' => 'p@p.com', 'password' => 'Senha@123', 'phone' => '22', 'city' => 'SP', 'state' => 'SP', 'type' => UserType::Cliente,
        ]);

        $request = StorageRequest::create([
            'company_id' => $client->id,
            'title' => 'Armazenamento',
            'status' => \App\Enums\RequestStatus::Pendente,
            'product_type' => ProductType::Congelados, 'quantity' => 100, 'unit' => UnitType::Pallets,
            'temp_min' => -18, 'temp_max' => -10, 'start_date' => now(), 'end_date' => now()->addDays(30),
            'target_city' => 'SP', 'target_state' => 'SP',
        ]);

        $space = Space::create([
            'company_id' => $partner->id,
            'name' => 'Câmara A', 'zip_code' => '000', 'address' => 'R', 'number' => '1', 'district' => 'D', 'city' => 'SP', 'state' => 'SP',
            'type' => SpaceType::Congelado, 'temp_min' => -20, 'temp_max' => -5, 'capacity' => 500, 'capacity_unit' => UnitType::Pallets,
            
            // --- CAMPOS OBRIGATÓRIOS ---
            'available_pallet_positions' => 500,
            'available_from' => now(),
            'available_until' => now()->addYear(),
            'contact_name' => 'Gerente',
            'contact_email' => 'g@g.com',
            'contact_phone' => '999999999',
        ]);

        return [$client, $partner, $request, $space];
    }

    public function test_client_can_request_quote_for_matched_space()
    {
        [$client, $partner, $request, $space] = $this->createScenario();

        Sanctum::actingAs($client);

        $payload = [
            'storage_request_id' => $request->id,
            'space_id' => $space->id,
        ];

        $response = $this->postJson('/api/quotes', $payload);

        $response->assertStatus(201);
        $this->assertDatabaseHas('quotes', [
            'storage_request_id' => $request->id,
            'space_id' => $space->id,
            'status' => QuoteStatus::Solicitado->value,
            'price' => null,
        ]);
    }

    public function test_partner_can_send_price_and_validity()
    {
        [$client, $partner, $request, $space] = $this->createScenario();

        $quote = Quote::create([
            'storage_request_id' => $request->id,
            'space_id' => $space->id,
            'status' => QuoteStatus::Solicitado,
        ]);

        Sanctum::actingAs($partner);

        $payload = [
            'price' => 15000.00,
            'valid_until' => now()->addDays(7)->format('Y-m-d'),
        ];

        $response = $this->putJson("/api/quotes/{$quote->id}", [
            'status' => 'respondido',
            'price' => 15000.00,
            'valid_until' => now()->addDays(7)->format('Y-m-d'),
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('quotes', [
            'id' => $quote->id,
            'price' => 15000.00,
            'status' => QuoteStatus::EmAnaliseAdmin->value,
        ]);
    }

    public function test_client_cannot_accept_quote_in_em_analise_admin()
    {
        [$client, $partner, $request, $space] = $this->createScenario();

        $quote = Quote::create([
            'storage_request_id' => $request->id,
            'space_id' => $space->id,
            'status' => QuoteStatus::EmAnaliseAdmin,
            'price' => 5000.00,
            'valid_until' => now()->addDays(5),
        ]);

        Sanctum::actingAs($client);

        $response = $this->putJson("/api/quotes/{$quote->id}", [
            'status' => QuoteStatus::Aceito->value,
        ]);

        $response->assertStatus(422)
            ->assertJsonFragment(['message' => 'Esta proposta ainda está em análise pela plataforma. Aguarde a aprovação.']);
    }

    public function test_client_cannot_accept_quote_without_price()
    {
        [$client, $partner, $request, $space] = $this->createScenario();

        $quote = Quote::create([
            'storage_request_id' => $request->id,
            'space_id' => $space->id,
            'status' => QuoteStatus::Solicitado,
            'price' => null,
        ]);

        Sanctum::actingAs($client);

        $response = $this->putJson("/api/quotes/{$quote->id}", [
            'status' => QuoteStatus::Aceito->value,
        ]);

        $response->assertStatus(422);
        $msg = strtolower($response->json('message') ?? '');
        $this->assertTrue(str_contains($msg, 'aceitar') || str_contains($msg, 'aceite') || str_contains($msg, 'disponível'), "Message should mention acceptance: {$msg}");
    }

    public function test_client_can_accept_priced_quote()
    {
        [$client, $partner, $request, $space] = $this->createScenario();

        $quote = Quote::create([
            'storage_request_id' => $request->id,
            'space_id' => $space->id,
            'status' => QuoteStatus::Respondido,
            'price' => 5000.00,
            'valid_until' => now()->addDays(5),
        ]);

        Sanctum::actingAs($client);

        $response = $this->putJson("/api/quotes/{$quote->id}", [
            'status' => QuoteStatus::Aceito->value,
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('quotes', [
            'id' => $quote->id,
            'status' => QuoteStatus::Aceito->value,
        ]);
    }

    public function test_partner_cannot_accept_his_own_quote()
    {
        [$client, $partner, $request, $space] = $this->createScenario();

        $quote = Quote::create([
            'storage_request_id' => $request->id,
            'space_id' => $space->id,
            'status' => QuoteStatus::Respondido,
            'price' => 5000.00,
            'valid_until' => now()->addDays(7),
        ]);

        Sanctum::actingAs($partner);

        // Parceiro tenta "aceitar" (apenas o cliente pode aceitar). A API pode retornar 403 ou processar sem aceitar.
        $response = $this->putJson("/api/quotes/{$quote->id}", [
            'status' => QuoteStatus::Aceito->value,
            'price' => 10000,
            'valid_until' => now()->addDays(7)->format('Y-m-d'),
        ]);

        // O parceiro não deve conseguir deixar a proposta como Aceito (apenas o cliente pode)
        $quote->refresh();
        $this->assertNotEquals(QuoteStatus::Aceito, $quote->status);
    }
}