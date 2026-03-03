<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class Cnpj implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // 1. Remove caracteres não numéricos
        $cnpj = preg_replace('/[^0-9]/', '', (string) $value);

        // 2. Valida tamanho e se todos dígitos são iguais
        if (strlen($cnpj) != 14 || preg_match('/(\d)\1{13}/', $cnpj)) {
            $fail('O :attribute não é um CNPJ válido.');

            return;
        }

        // 3. Validação matemática (Com Cast para (int) para agradar o PHPStan)
        for ($i = 0, $j = 5, $soma = 0; $i < 12; $i++) {
            $soma += ((int) $cnpj[$i]) * $j; // <--- CORREÇÃO AQUI
            $j = ($j == 2) ? 9 : $j - 1;
        }
        $resto = $soma % 11;
        if ($cnpj[12] != ($resto < 2 ? 0 : 11 - $resto)) {
            $fail('O :attribute é inválido (erro no dígito verificador).');

            return;
        }

        for ($i = 0, $j = 6, $soma = 0; $i < 13; $i++) {
            $soma += ((int) $cnpj[$i]) * $j; // <--- CORREÇÃO AQUI
            $j = ($j == 2) ? 9 : $j - 1;
        }
        $resto = $soma % 11;
        if ($cnpj[13] != ($resto < 2 ? 0 : 11 - $resto)) {
            $fail('O :attribute é inválido (erro no dígito verificador).');

            return;
        }
    }
}
