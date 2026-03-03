<?php

namespace Tests\Feature;

use App\Enums\ProductType;
use App\Enums\RequestStatus;
use App\Enums\SpaceType;
use App\Enums\UnitType;
use App\Enums\UserType;
use App\Models\Company;
use App\Models\Quote;
use App\Models\Space;
use App\Models\StorageRequest;
use App\Notifications\QuoteStatusChanged;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_list_notifications(): void
    {
        $company = Company::factory()->create(['type' => UserType::Cliente]);
        Sanctum::actingAs($company);

        $response = $this->getJson('/api/notifications');

        $response->assertStatus(200);
        $response->assertJsonStructure(['data', 'meta' => ['unread_count', 'total']]);
    }

    public function test_authenticated_user_can_get_unread_count(): void
    {
        $company = Company::factory()->create();
        Sanctum::actingAs($company);

        $response = $this->getJson('/api/notifications/unread-count');

        $response->assertStatus(200);
        $response->assertJsonStructure(['unread_count']);
    }

    public function test_user_can_mark_notification_as_read(): void
    {
        $company = Company::factory()->create();
        $partner = Company::factory()->create();
        $request = StorageRequest::create([
            'company_id' => $company->id,
            'title' => 'Armazenamento',
            'status' => RequestStatus::Pendente,
            'product_type' => ProductType::Congelados,
            'quantity' => 100,
            'unit' => UnitType::Pallets,
            'temp_min' => -20,
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
            'capacity_unit' => UnitType::Pallets,
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
            'price' => 1000,
            'valid_until' => now()->addDays(7),
            'status' => \App\Enums\QuoteStatus::Respondido,
        ]);
        $company->notify(new QuoteStatusChanged('Teste', 'respondido', $quote->load(['space.company', 'storageRequest'])));
        $notification = $company->notifications()->first();

        Sanctum::actingAs($company);

        $response = $this->patchJson("/api/notifications/{$notification->id}/read");

        $response->assertStatus(200);
        $response->assertJson(['message' => 'Notificação marcada como lida']);
    }

    public function test_user_can_mark_all_notifications_as_read(): void
    {
        $company = Company::factory()->create();
        Sanctum::actingAs($company);

        $response = $this->patchJson('/api/notifications/read-all');

        $response->assertStatus(200);
        $response->assertJsonStructure(['message', 'marked_count']);
    }

    public function test_unauthenticated_user_cannot_access_notifications(): void
    {
        $response = $this->getJson('/api/notifications');
        $response->assertStatus(401);
    }
}
