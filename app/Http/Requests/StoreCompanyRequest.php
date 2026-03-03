<?php

namespace App\Http\Requests;

use App\Enums\UserType;
use App\Rules\Cnpj;
use App\Rules\Turnstile;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\Rules\Password;

class StoreCompanyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'trade_name' => 'required|string',
            'legal_name' => 'required|string|min:3',
            'cnpj' => ['required', 'string', 'unique:companies', new Cnpj],
            'email' => 'required|email|unique:companies',
            'phone' => 'required|string',
            'address_street' => 'required|string',
            'address_number' => 'required|string',
            'district' => 'required|string',
            'city' => 'required|string',
            'state' => 'required|string|size:2',
            'password' => [
                'required', 'confirmed',
                Password::min(8)->mixedCase()->numbers()->symbols(),
                'turnstile_token' => ['required', 'string', new Turnstile],
            ],
        ];
    }
}
