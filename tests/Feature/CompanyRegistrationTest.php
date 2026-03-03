<?php

namespace Tests\Feature;

use App\Enums\UserType;
use App\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CompanyRegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_can_register_a_company()
    {
        $payload = [
            'trade_name' => 'Fica Frio Logística',
            'legal_name' => 'Fica Frio Logística Ltda',
            'cnpj' => '12.345.678/0001-95',
            'email' => 'contato@ficafrio.com.br',
            'password' => 'Senha@123',
            'password_confirmation' => 'Senha@123',
            'phone' => '11999999999',
            'address_street' => 'Av Paulista',
            'address_number' => '1000',
            'district' => 'Bela Vista',
            'city' => 'São Paulo',
            'state' => 'SP',
            // 'type' removido do request, backend assume Cliente
        ];

        $response = $this->postJson('api/companies', $payload);

        $response->assertStatus(201);
        $this->assertDatabaseHas('companies', [
            'cnpj' => '12.345.678/0001-95',
            'email' => 'contato@ficafrio.com.br',
            'type' => UserType::Cliente->value, // Verifica se foi gravado correto
        ]);
    }

    public function test_it_can_list_all_companies()
    {
        $company = Company::create([
            'trade_name' => 'Empresa Teste', 'legal_name' => 'Empresa Teste Ltda',
            'cnpj' => '11.111.111/0001-11', 'email' => 'teste@email.com',
            'password' => 'Senha@123', 'phone' => '11999999999',
            'city' => 'São Paulo', 'state' => 'SP',
            'type' => UserType::Cliente,
            'address_street' => 'Rua Teste', 'address_number' => '123',
        ]);

        Sanctum::actingAs($company);

        $response = $this->getJson('api/companies');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    public function test_it_can_update_a_company()
    {
        $company = Company::create([
            'trade_name' => 'Empresa Antiga', 'legal_name' => 'Empresa Antiga Ltda',
            'cnpj' => '22.222.222/0001-22', 'email' => 'antigo@email.com',
            'password' => 'Senha@123', 'phone' => '11999999999',
            'city' => 'Rio de Janeiro', 'state' => 'RJ',
            'type' => UserType::Cliente,
            'address_street' => 'Rua Antiga',
        ]);

        Sanctum::actingAs($company);

        $payload = [
            'trade_name' => 'Nome Novo',
            'email' => 'novo@email.com',
        ];

        $response = $this->putJson("/api/companies/{$company->id}", $payload);

        $response->assertStatus(200);
        $this->assertDatabaseHas('companies', [
            'id' => $company->id,
            'trade_name' => 'Nome Novo',
            'email' => 'novo@email.com',
        ]);
    }

    public function test_it_can_delete_a_company()
    {
        $company = Company::create([
            'trade_name' => 'Empresa Delete', 'legal_name' => 'Deletar Ltda',
            'cnpj' => '12.345.678/0001-95', 'email' => 'delete@email.com',
            'password' => 'Senha@123', 'phone' => '11999999999',
            'city' => 'Curitiba', 'state' => 'PR',
            'type' => UserType::Cliente,
            'address_street' => 'Rua Fim',
        ]);

        Sanctum::actingAs($company);

        $this->deleteJson("/api/companies/{$company->id}")
            ->assertStatus(204);

        $this->assertSoftDeleted('companies', ['id' => $company->id]);
    }

    public function test_it_validates_bad_email()
    {
        $payload = [
            'trade_name' => 'Empresa Errada', 'legal_name' => 'Empresa Errada Ltda',
            'cnpj' => '12.345.678/0001-90', 'email' => 'nao-e-um-email',
            'phone' => '11999999999', 'address_street' => 'Rua teste',
            'city' => 'SP', 'state' => 'SP',
            'password' => 'Senha@123', 'password_confirmation' => 'Senha@123',
        ];

        $this->postJson('api/companies', $payload)
            ->assertStatus(422)
            ->assertJsonValidationErrors(['email']);
    }

    public function test_it_validates_bad_cnpj_format()
    {
        $payload = [
            'trade_name' => 'Empresa CNPJ Ruim', 'legal_name' => 'Ruim Ltda',
            'cnpj' => '12345678000190', 'email' => 'certo@email.com',
            'phone' => '11999999999', 'address_street' => 'Rua Teste',
            'city' => 'SP', 'state' => 'SP',
            'password' => 'Senha@123', 'password_confirmation' => 'Senha@123',
        ];

        $this->postJson('api/companies', $payload)
            ->assertStatus(422)
            ->assertJsonValidationErrors(['cnpj']);
    }

    public function test_it_validates_mathematically_invalid_cnpj()
    {
        $payload = [
            'trade_name' => 'Empresa Fake', 'legal_name' => 'Fake Ltda',
            'cnpj' => '00.000.000/0001-00', 'email' => 'fake@email.com',
            'password' => 'Senha@123', 'password_confirmation' => 'Senha@123',
            'phone' => '11999999999', 'city' => 'SP', 'state' => 'SP',
            'address_street' => 'Rua Fake',
        ];

        $this->postJson('api/companies', $payload)
            ->assertStatus(422)
            ->assertJsonValidationErrors(['cnpj']);
    }

    public function test_it_requires_password_confirmation()
    {
        $payload = [
            'trade_name' => 'Empresa Senha', 'legal_name' => 'Senha Ltda',
            'cnpj' => '99.999.999/0001-99', 'email' => 'senha@email.com',
            'phone' => '11999999999', 'address_street' => 'Rua Teste',
            'city' => 'SP', 'state' => 'SP',
            'password' => 'Senha@123', 'password_confirmation' => 'senhaERRADA',
        ];

        $this->postJson('api/companies', $payload)
            ->assertStatus(422)
            ->assertJsonValidationErrors(['password']);
    }

    public function test_company_cannot_change_sensitive_data_on_update()
    {
        $company = Company::create([
            'trade_name' => 'Cliente Tentando Virar Parceiro',
            'legal_name' => 'Cliente Ltda',
            'cnpj' => '12.345.678/0001-95',
            'email' => 'cliente@teste.com',
            'password' => 'Senha@123',
            'phone' => '11999999999',
            'city' => 'SP', 'state' => 'SP',
            'type' => UserType::Cliente,
            'address_street' => 'Rua A',
        ]);

        Sanctum::actingAs($company);

        $payload = [
            'trade_name' => 'Nome Mudou',
            'type' => UserType::Admin->value, // Tentativa de hack para virar Admin
            'cnpj' => '99.999.999/0001-99',
        ];

        $response = $this->putJson("/api/companies/{$company->id}", $payload);

        $response->assertStatus(200);

        $this->assertDatabaseHas('companies', [
            'id' => $company->id,
            'trade_name' => 'Nome Mudou',
            'type' => UserType::Cliente->value, // Garante que ignorou a mudança de type
            'cnpj' => '12.345.678/0001-95',
        ]);
    }
}