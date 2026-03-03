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

class PaymentTest extends TestCase
{
    use RefreshDatabase;

    private function createAcceptedQuoteScenario(): array
    {
        $client = Company::factory()->create(['type' => UserType::Cliente]);
        $partner = Company::factory()->create(['type' => UserType::Cliente]);

        $request = StorageRequest::create([
            'company_id' => $client->id,
            'title' => 'Armazenamento',
            'product_type' => ProductType::Congelados->value,
            'quantity' => 100,
            'unit' => UnitType::Pallets->value,
            'temp_min' => -18,
            'temp_max' => -10,
            'start_date' => now(),
            'end_date' => now()->addDays(30),
            'target_city' => 'SP',
            'target_state' => 'SP',
        ]);

        $space = Space::create([
            'company_id' => $partner->id,
            'name' => 'Camara A',
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
        ]);

        $quote = Quote::create([
            'storage_request_id' => $request->id,
            'space_id' => $space->id,
            'price' => 1500.00,
            'valid_until' => now()->addDays(7),
            'status' => QuoteStatus::Aceito,
        ]);

        return [$client, $partner, $request, $space, $quote];
    }

    public function test_authenticated_user_can_create_payment_for_accepted_quote(): void
    {
        [$client, $partner, $request, $space, $quote] = $this->createAcceptedQuoteScenario();
        Sanctum::actingAs($client);

        $response = $this->postJson("/api/quotes/{$quote->id}/payment");

        $response->assertStatus(201);
        $response->assertJsonStructure(['data' => ['id', 'amount', 'platform_fee', 'net_amount', 'status']]);
        $this->assertDatabaseHas('payments', [
            'quote_id' => $quote->id,
            'company_id' => $client->id,
            'space_owner_id' => $partner->id,
        ]);
    }

    public function test_user_cannot_create_payment_for_quote_they_dont_own(): void
    {
        [$client, $partner, $request, $space, $quote] = $this->createAcceptedQuoteScenario();
        Sanctum::actingAs($partner);

        $response = $this->postJson("/api/quotes/{$quote->id}/payment");

        $response->assertStatus(403);
    }

    public function test_user_can_list_own_payments(): void
    {
        [$client] = $this->createAcceptedQuoteScenario();
        Sanctum::actingAs($client);

        $response = $this->getJson('/api/payments');

        $response->assertStatus(200);
    }

    public function test_unauthenticated_user_cannot_access_payments(): void
    {
        $response = $this->getJson('/api/payments');
        $response->assertStatus(401);
    }
}
