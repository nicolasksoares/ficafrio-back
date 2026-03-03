<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Platform Fee Percentage
    |--------------------------------------------------------------------------
    |
    | Taxa percentual que a plataforma cobra sobre cada pagamento.
    | Valor padrão: 10% (comum para marketplaces)
    |
    */
    'platform_fee_percentage' => env('PLATFORM_FEE_PERCENTAGE', 10),

    /*
    |--------------------------------------------------------------------------
    | Payment Gateway
    |--------------------------------------------------------------------------
    |
    | Gateway de pagamento a ser usado. Quando null, usa implementação stub.
    | Opções futuras: 'asaas', 'mercadopago', 'pagseguro', etc.
    |
    */
    'gateway' => env('PAYMENT_GATEWAY', null),

    /*
    |--------------------------------------------------------------------------
    | Gateway Configuration
    |--------------------------------------------------------------------------
    |
    | Para Stripe: PAYMENT_GATEWAY=stripe, STRIPE_SECRET_KEY e STRIPE_WEBHOOK_SECRET.
    | Chaves genéricas (api_key, webhook_secret) são usadas por outros gateways.
    |
    */
    'gateway_config' => [
        'api_key' => env('PAYMENT_GATEWAY_API_KEY'),
        'api_secret' => env('PAYMENT_GATEWAY_API_SECRET'),
        'webhook_secret' => env('PAYMENT_GATEWAY_WEBHOOK_SECRET'),
        'environment' => env('PAYMENT_GATEWAY_ENV', 'sandbox'),
        'stripe_secret_key' => env('STRIPE_SECRET_KEY'),
        'stripe_webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Payment Expiration Days
    |--------------------------------------------------------------------------
    |
    | Dias de validade para cada método de pagamento.
    |
    */
    'expiration_days' => [
        'pix' => 1,
        'credit_card' => 0, // Imediato
        'boleto' => 7,
    ],

    /*
    |--------------------------------------------------------------------------
    | Webhook Configuration
    |--------------------------------------------------------------------------
    |
    | Configurações para recebimento de webhooks do gateway.
    |
    */
    'webhook' => [
        'route' => '/api/payments/webhook',
        'verify_signature' => env('PAYMENT_WEBHOOK_VERIFY', true),
    ],
];

