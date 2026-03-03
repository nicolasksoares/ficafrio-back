<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCompanyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'trade_name' => 'sometimes|string',
            'legal_name' => 'sometimes|string|min:3',
            'email' => [
                'sometimes',
                'email',
                Rule::unique('companies')->ignore($this->user()->id),
            ],
            'phone' => 'sometimes|string',
            'address_street' => 'sometimes|string',
            'address_number' => 'sometimes|string',
            'district' => 'sometimes|string',
            'city' => 'sometimes|string',
            'state' => 'sometimes|string|size:2',

        ];
    }
}
