<?php

namespace App\Exceptions;

use Exception;

class PaymentAlreadyExistsException extends Exception
{
    protected $message = 'Já existe um pagamento para esta cotação.';

    public function __construct(?string $message = null, int $code = 409)
    {
        parent::__construct($message ?? $this->message, $code);
    }

    public function render($request)
    {
        return response()->json([
            'message' => $this->getMessage(),
            'error' => 'payment_already_exists',
        ], $this->getCode());
    }
}

