<?php

namespace App\Exceptions;

use Exception;

class InvalidWebhookSignatureException extends Exception
{
    protected $message = 'Assinatura do webhook inválida.';

    public function __construct(?string $message = null, int $code = 401)
    {
        parent::__construct($message ?? $this->message, $code);
    }

    public function render($request)
    {
        return response()->json([
            'message' => $this->getMessage(),
            'error' => 'invalid_webhook_signature',
        ], $this->getCode());
    }
}

