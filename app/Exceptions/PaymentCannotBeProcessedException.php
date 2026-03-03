<?php

namespace App\Exceptions;

use Exception;

class PaymentCannotBeProcessedException extends Exception
{
    protected $message = 'Este pagamento não pode ser processado.';

    public function __construct(?string $message = null, int $code = 422)
    {
        parent::__construct($message ?? $this->message, $code);
    }

    public function render($request)
    {
        return response()->json([
            'message' => $this->getMessage(),
            'error' => 'payment_cannot_be_processed',
        ], $this->getCode());
    }
}

