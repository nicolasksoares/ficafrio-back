<?php

namespace App\Http\Requests;

use App\Enums\SpaceType;
use App\Rules\ValidImageMime;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class StoreSpaceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation()
    {
        $map = [
            'street_address' => 'address',
            'min_temperature_celsius' => 'temp_min',
            'max_temperature_celsius' => 'temp_max',
            'total_pallet_positions' => 'capacity',
            'chamber_type' => 'type',
            'opening_hours' => 'operating_hours',
        ];

        foreach ($map as $frontField => $dbField) {
            if ($this->has($frontField)) {
                $this->merge([$dbField => $this->input($frontField)]);
            }
        }
    }

    public function rules(): array
    {
        return [
            'name' => [
                'required', 'string', 'min:3', 'max:255',
                Rule::unique('spaces', 'name')->where(function ($query) {
                    return $query->where('company_id', $this->user()->id);
                }),
            ],
            'description' => ['nullable', 'string'],
            'address' => ['required', 'string'],
            'number' => ['required', 'string', 'max:20'],
            'district' => ['required', 'string', 'max:100'],
            'city' => ['required', 'string'],
            'state' => ['required', 'string', 'size:2'],
            'zip_code' => ['required', 'string', 'max:20'],
            
            'temp_min' => ['required', 'numeric', 'between:-100,100'],
            'temp_max' => ['required', 'numeric', 'gte:temp_min'],
            'capacity' => ['required', 'integer', 'min:1'],
            'available_pallet_positions' => ['required', 'integer', 'min:0', 'lte:capacity'],
            'type' => ['required', new Enum(SpaceType::class)],
            
            'contact_name' => ['required', 'string', 'max:255'],
            'contact_email' => ['required', 'email'],
            'contact_phone' => ['required', 'string'],
            
            'operating_hours' => ['nullable', 'string', 'max:255'],
            'has_anvisa' => ['boolean'],
            'has_security' => ['boolean'],
            'has_generator' => ['boolean'],
            'active' => ['boolean'],
        ];
    }
}