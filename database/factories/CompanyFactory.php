<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use App\Enums\UserType;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Company>
 */
class CompanyFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'trade_name' => $this->faker->company(),
            'legal_name' => $this->faker->company() . ' Ltda',
            'cnpj' => $this->faker->numerify('##############'),
            'email' => $this->faker->unique()->safeEmail(),
            'password' => 'password', 
            'phone' => $this->faker->phoneNumber(),
            
            // Endereço
            'address_street' => $this->faker->streetName(),
            'address_number' => $this->faker->buildingNumber(),
            'district' => $this->faker->word(),
            'city' => $this->faker->city(),
            'state' => $this->faker->stateAbbr(),
            'zip_code' => $this->faker->postcode(),
            
            'type' => UserType::Cliente, 
            'active' => true,
        ];
    }
}