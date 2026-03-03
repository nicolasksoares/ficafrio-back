<?php

namespace App\Exceptions;

use Exception;

class PaymentNotFoundException extends Exception
{
    protected $message = 'Pagamento não encontrado.';

    public function __construct(?string $message = null, int $code = 404)
    {
        parent::__construct($message ?? $this->message, $code);
    }

    public function render($request)
    {
        return response()->json([
            'message' => $this->getMessage(),
            'error' => 'payment_not_found',
        ], $this->getCode());
    }
}

