<?php

namespace Tests\Feature;

use App\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;
use App\Enums\UserType;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_company_can_login_and_get_token()
    {
        $company = Company::factory()->create([
            'email' => 'login@ficafrio.com',
            'password' => 'Senha@123',
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'login@ficafrio.com',
            'password' => 'Senha@123',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['token']);
    }

    public function test_a_company_cannot_login_with_wrong_password()
    {
        Company::factory()->create([
            'email' => 'login@ficafrio.com',
            'password' => 'Senha@123',
        ]);

        $response = $this->postJson('/api/login', [
            'email' => 'login@ficafrio.com',
            'password' => 'senha-ERRADA',
        ]);

        $response->assertStatus(401);
    }

    public function test_it_cannot_access_protected_routes_without_token()
    {
        $response = $this->getJson('/api/companies');
        $response->assertStatus(401);
    }

    public function test_a_company_can_logout()
    {
        $company = Company::factory()->create();
        $token = $company->createToken('test')->plainTextToken;
    
        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/logout')
            ->assertStatus(200);
    
        $this->app->get('auth')->forgetGuards();
    
        $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/companies')
            ->assertStatus(401);
    }

    public function test_authenticated_user_can_see_own_profile()
    {
        $company = Company::factory()->create([
            'email' => 'me@teste.com',
        ]);

        Sanctum::actingAs($company);

        $this->getJson('/api/me')
            ->assertStatus(200)
            ->assertJsonFragment(['email' => 'me@teste.com']);
    }

    // --- TESTES DE RECUPERAÇÃO DE SENHA ---

    public function test_it_can_send_reset_password_link()
    {
        Company::factory()->create([
            'email' => 'forgot@teste.com',
        ]);

        $response = $this->postJson('/api/forgot-password', [
            'email' => 'forgot@teste.com',
        ]);

        $response->assertStatus(200)
            ->assertJson(['status' => trans('passwords.sent')]);
    }

    public function test_it_can_reset_password_with_valid_token()
    {
        $company = Company::factory()->create([
            'email' => 'reset@teste.com',
            'password' => 'SenhaAntiga@123',
        ]);

        $token = Password::broker('companies')->createToken($company);

        $response = $this->postJson('/api/reset-password', [
            'token' => $token,
            'email' => 'reset@teste.com',
            'password' => 'NovaSenha@123',
            'password_confirmation' => 'NovaSenha@123',
        ]);

        $response->assertStatus(200)
            ->assertJson(['status' => trans('passwords.reset')]);

        $company->refresh();
        $this->assertTrue(Hash::check('NovaSenha@123', $company->password));
    }
}