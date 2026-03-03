<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Enums\PaymentMethod;

class ProcessPaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Autorização feita no controller
    }

    public function rules(): array
    {
        return [
            'payment_method' => [
                'required',
                'string',
                'in:' . implode(',', array_column(PaymentMethod::cases(), 'value')),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'payment_method.required' => 'O método de pagamento é obrigatório.',
            'payment_method.in' => 'Método de pagamento inválido. Use: pix, credit_card ou boleto.',
        ];
    }
}

