<?php

namespace Tests\Feature;

use App\Enums\ProductType;
use App\Enums\QuoteStatus;
use App\Enums\RequestStatus;
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

class AdminQuoteTest extends TestCase
{
    use RefreshDatabase;

    private function createScenario(): array
    {
        $admin = Company::create([
            'trade_name' => 'Admin',
            'legal_name' => 'Admin Ltda',
            'cnpj' => '00.000.000/0001-00',
            'email' => 'admin@ficafrio.com',
            'password' => 'Senha@123',
            'phone' => '00',
            'city' => 'SP',
            'state' => 'SP',
            'type' => UserType::Admin,
        ]);

        $client = Company::create([
            'trade_name' => 'Cliente',
            'legal_name' => 'C Ltda',
            'cnpj' => '11.111.111/0001-11',
            'email' => 'c@c.com',
            'password' => 'Senha@123',
            'phone' => '11',
            'city' => 'SP',
            'state' => 'SP',
            'type' => UserType::Cliente,
        ]);

        $partner = Company::create([
            'trade_name' => 'Parceiro',
            'legal_name' => 'P Ltda',
            'cnpj' => '22.222.222/0001-22',
            'email' => 'p@p.com',
            'password' => 'Senha@123',
            'phone' => '22',
            'city' => 'SP',
            'state' => 'SP',
            'type' => UserType::Cliente,
        ]);

        $request = StorageRequest::create([
            'company_id' => $client->id,
            'title' => 'Armazenamento frigorífico',
            'status' => RequestStatus::Pendente,
            'product_type' => ProductType::Congelados,
            'quantity' => 100,
            'unit' => UnitType::Pallets,
            'temp_min' => -18,
            'temp_max' => -10,
            'start_date' => now(),
            'end_date' => now()->addDays(30),
            'target_city' => 'SP',
            'target_state' => 'SP',
        ]);

        $space = Space::create([
            'company_id' => $partner->id,
            'name' => 'Câmara A',
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
            'capacity_unit' => UnitType::Pallets,
            'available_pallet_positions' => 500,
            'available_from' => now(),
            'available_until' => now()->addYear(),
            'contact_name' => 'Gerente',
            'contact_email' => 'g@g.com',
            'contact_phone' => '999999999',
        ]);

        return [$admin, $client, $partner, $request, $space];
    }

    public function test_admin_can_list_quotes_pending_approval(): void
    {
        [$admin, $client, $partner, $request, $space] = $this->createScenario();

        Quote::create([
            'storage_request_id' => $request->id,
            'space_id' => $space->id,
            'status' => QuoteStatus::EmAnaliseAdmin,
            'price' => 10000,
            'valid_until' => now()->addDays(7),
        ]);

        Sanctum::actingAs($admin);
        $response = $this->getJson('/api/admin/quotes');

        $response->assertStatus(200);
        $this->assertGreaterThanOrEqual(1, count($response->json('data') ?? []));
    }

    public function test_admin_can_approve_quote(): void
    {
        [$admin, $client, $partner, $request, $space] = $this->createScenario();

        $quote = Quote::create([
            'storage_request_id' => $request->id,
            'space_id' => $space->id,
            'status' => QuoteStatus::EmAnaliseAdmin,
            'price' => 10000,
            'valid_until' => now()->addDays(7),
        ]);

        Sanctum::actingAs($admin);
        $response = $this->postJson("/api/admin/quotes/{$quote->id}/approve", [
            'approved_price' => 9500,
        ]);

        $response->assertStatus(200);
        $quote->refresh();
        $this->assertEquals(QuoteStatus::Respondido->value, $quote->status->value);
        $this->assertEquals(9500, (float) $quote->price);
        $this->assertNotNull($quote->admin_approved_at);
    }

    public function test_admin_can_reject_quote(): void
    {
        [$admin, $client, $partner, $request, $space] = $this->createScenario();

        $quote = Quote::create([
            'storage_request_id' => $request->id,
            'space_id' => $space->id,
            'status' => QuoteStatus::EmAnaliseAdmin,
            'price' => 10000,
            'valid_until' => now()->addDays(7),
        ]);

        Sanctum::actingAs($admin);
        $response = $this->postJson("/api/admin/quotes/{$quote->id}/reject", [
            'reason' => 'Valor acima da tabela.',
        ]);

        $response->assertStatus(200);
        $this->assertSoftDeleted('quotes', ['id' => $quote->id]);
    }

    public function test_admin_cannot_approve_expired_quote(): void
    {
        [$admin, $client, $partner, $request, $space] = $this->createScenario();

        $quote = Quote::create([
            'storage_request_id' => $request->id,
            'space_id' => $space->id,
            'status' => QuoteStatus::EmAnaliseAdmin,
            'price' => 10000,
            'valid_until' => now()->subDay(),
        ]);

        Sanctum::actingAs($admin);
        $response = $this->postJson("/api/admin/quotes/{$quote->id}/approve");

        $response->assertStatus(422)
            ->assertJsonFragment(['message' => 'Esta proposta expirou. Não é possível aprovar.']);
    }
}
