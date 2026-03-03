<?php

namespace Tests\Feature;

use App\Enums\UserType;
use App\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class AdminAccessTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function guests_cannot_access_admin_routes()
    {
        $response = $this->getJson('/api/admin/spaces');
        $response->assertStatus(401);
    }

    #[Test]
    public function regular_companies_cannot_access_admin_routes()
    {
        $company = Company::factory()->create([
            'type' => UserType::Cliente,
        ]);

        $response = $this->actingAs($company)->getJson('/api/admin/spaces');
        $response->assertStatus(403);
    }

    #[Test]
    public function admins_can_access_admin_routes()
    {
        $admin = Company::factory()->create([
            'type' => UserType::Admin,
        ]);

        $response = $this->actingAs($admin)->getJson('/api/admin/spaces');
        $response->assertStatus(200);
    }

    #[Test]
    public function regular_companies_cannot_access_admin_quotes()
    {
        $company = Company::factory()->create([
            'type' => UserType::Cliente,
        ]);

        $response = $this->actingAs($company)->getJson('/api/admin/quotes');
        $response->assertStatus(403);
    }
}