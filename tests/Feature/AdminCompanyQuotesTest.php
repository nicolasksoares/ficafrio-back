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

class AdminCompanyQuotesTest extends TestCase
{
    use RefreshDatabase;

    private function createScenario(): array
    {
        $admin = Company::factory()->create(['type' => UserType::Admin]);
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
            'status' => QuoteStatus::Respondido,
        ]);

        return [$admin, $client, $partner, $request, $space, $quote];
    }

    public function test_admin_can_list_company_quotes(): void
    {
        [$admin, $client, $partner, $request, $space, $quote] = $this->createScenario();
        Sanctum::actingAs($admin);

        $response = $this->getJson("/api/admin/companies/{$client->id}/quotes");

        $response->assertStatus(200);
        $response->assertJsonStructure(['data']);
    }

    public function test_admin_can_filter_company_quotes_by_status(): void
    {
        [$admin, $client] = $this->createScenario();
        Sanctum::actingAs($admin);

        $response = $this->getJson("/api/admin/companies/{$client->id}/quotes?status=respondido,aceito");

        $response->assertStatus(200);
    }

    public function test_non_admin_cannot_list_company_quotes(): void
    {
        [$admin, $client, $partner] = $this->createScenario();
        Sanctum::actingAs($partner);

        $response = $this->getJson("/api/admin/companies/{$client->id}/quotes");

        $response->assertStatus(403);
    }
}
