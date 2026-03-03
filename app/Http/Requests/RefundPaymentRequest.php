<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RefundPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Autorização feita no controller (apenas admin)
    }

    public function rules(): array
    {
        return [
            'reason' => [
                'nullable',
                'string',
                'max:500',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'reason.max' => 'O motivo do reembolso não pode ter mais de 500 caracteres.',
        ];
    }
}

