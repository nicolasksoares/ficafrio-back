<?php

namespace Tests\Feature;

use App\Enums\SpaceType;
use App\Enums\UserType;
use App\Models\Company;
use App\Models\Space;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SpaceImageTest extends TestCase
{
    use RefreshDatabase;

    public function test_partner_can_upload_photo_to_his_space()
    {
        if (!extension_loaded('gd')) {
            $this->markTestSkipped('Extensão GD não instalada - necessário para criar imagens fake.');
        }
        Storage::fake('public');

        $partner = Company::create([
            'trade_name' => 'Parceiro', 'legal_name' => 'P Ltda', 'cnpj' => '11.111.111/0001-11', 'email' => 'p@p.com', 'password' => 'Senha@123', 'phone' => '22', 'city' => 'SP', 'state' => 'SP', 'type' => UserType::Cliente,
        ]);

        $space = Space::create([
            'company_id' => $partner->id,
            'name' => 'Câmara A', 'zip_code' => '0', 'address' => 'R', 'number' => '1', 'district' => 'D', 'city' => 'SP', 'state' => 'SP',
            'type' => SpaceType::Congelado, 'temp_min' => -20, 'temp_max' => -5, 'capacity' => 500,
            
            // --- CAMPOS OBRIGATÓRIOS ---
            'available_pallet_positions' => 500,
            'available_from' => now(),
            'available_until' => now()->addYear(),
            'contact_name' => 'Gerente',
            'contact_email' => 'g@g.com',
            'contact_phone' => '999999999',
        ]);

        Sanctum::actingAs($partner);

        $file = UploadedFile::fake()->image('foto.jpg', 300, 300);

        $response = $this->postJson("/api/spaces/{$space->id}/photos", [
            'photo' => $file,
        ]);

        $response->assertStatus(201);

        Storage::disk('public')->assertExists("spaces/{$space->id}/".$file->hashName());

        $this->assertDatabaseHas('space_photos', [
            'space_id' => $space->id,
        ]);
    }

    public function test_it_validates_image_format()
    {
        Storage::fake('public');

        $partner = Company::create([
            'trade_name' => 'Parceiro', 'legal_name' => 'P Ltda', 'cnpj' => '11.111.111/0001-11', 'email' => 'p@p.com', 'password' => 'Senha@123', 'phone' => '22', 'city' => 'SP', 'state' => 'SP', 'type' => UserType::Cliente,
        ]);

        $space = Space::create([
            'company_id' => $partner->id, 'name' => 'Câmara A', 'zip_code' => '0', 'address' => 'R', 'number' => '1', 'district' => 'D', 'city' => 'SP', 'state' => 'SP',
            'type' => SpaceType::Congelado, 'temp_min' => -20, 'temp_max' => -5, 'capacity' => 500,
            
            // --- CAMPOS OBRIGATÓRIOS ---
            'available_pallet_positions' => 500,
            'available_from' => now(),
            'available_until' => now()->addYear(),
            'contact_name' => 'Gerente',
            'contact_email' => 'g@g.com',
            'contact_phone' => '999999999',
        ]);

        Sanctum::actingAs($partner);

        $file = UploadedFile::fake()->create('documento.pdf', 100);

        $this->postJson("/api/spaces/{$space->id}/photos", ['photo' => $file])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['photo']);
    }
}