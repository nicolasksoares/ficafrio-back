<?php

namespace App\Services;

use App\Contracts\PaymentGatewayInterface;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class StripeGatewayService implements PaymentGatewayInterface
{
    public function __construct()
    {
        $stripeInit = base_path('vendor/stripe/stripe-php/init.php');
        if (file_exists($stripeInit)) {
            require_once $stripeInit;
        }

        $secretKey = config('payment.gateway_config.stripe_secret_key');
        if ($secretKey) {
            \Stripe\Stripe::setApiKey($secretKey);
        }
    }

    /**
     * Cria uma Checkout Session no Stripe e retorna a URL para o cliente pagar.
     */
    public function createPayment(Payment $payment, string $method): array
    {
        $secretKey = config('payment.gateway_config.stripe_secret_key');
        if (!$secretKey) {
            throw new \InvalidArgumentException('STRIPE_SECRET_KEY não configurada.');
        }

        $frontendUrl = rtrim(env('FRONTEND_URL', 'http://localhost:5173'), '/');

        $successUrl = "{$frontendUrl}/dashboard?section=payments&payment_success=1&payment_id={$payment->id}";
        $cancelUrl = "{$frontendUrl}/dashboard?section=payments&payment_cancelled=1";

        $paymentMethodTypes = $this->mapPaymentMethodToStripe($method);
        $amountInCentavos = (int) round((float) $payment->amount * 100);

        $sessionParams = [
            'payment_method_types' => $paymentMethodTypes,
            'line_items' => [
                [
                    'price_data' => [
                        'currency' => 'brl',
                        'product_data' => [
                            'name' => 'Armazenagem FicaFrio - Pagamento #' . $payment->id,
                            'description' => 'Reserva de espaço em câmara fria',
                        ],
                        'unit_amount' => $amountInCentavos,
                    ],
                    'quantity' => 1,
                ],
            ],
            'mode' => 'payment',
            'success_url' => $successUrl,
            'cancel_url' => $cancelUrl,
            'metadata' => [
                'payment_id' => (string) $payment->id,
            ],
        ];

        if ($method === 'pix') {
            $sessionParams['payment_method_options'] = [
                'pix' => [
                    'expires_after_seconds' => 86400, // 1 dia
                ],
            ];
        }

        if ($method === 'boleto') {
            $sessionParams['payment_method_options'] = [
                'boleto' => [
                    'expires_after_days' => 7,
                ],
            ];
        }

        $session = \Stripe\Checkout\Session::create($sessionParams);

        $expiresAt = null;
        if ($session->expires_at) {
            $expiresAt = date('c', $session->expires_at);
        }

        Log::info('Stripe Checkout Session created', [
            'payment_id' => $payment->id,
            'session_id' => $session->id,
            'method' => $method,
        ]);

        return [
            'transaction_id' => $session->id,
            'payment_url' => $session->url,
            'payment_code' => null,
            'status' => 'processing',
            'expires_at' => $expiresAt,
        ];
    }

    public function getPaymentStatus(string $transactionId): array
    {
        $session = \Stripe\Checkout\Session::retrieve($transactionId, ['expand' => ['payment_intent']]);
        $status = 'processing';
        $paidAt = null;
        $amount = 0;

        if ($session->payment_status === 'paid') {
            $status = 'paid';
            $amount = $session->amount_total ? $session->amount_total / 100 : 0;
            if ($session->payment_intent && isset($session->payment_intent->latest_charge)) {
                $charge = $session->payment_intent->latest_charge;
                if (is_object($charge) && isset($charge->created)) {
                    $paidAt = date('c', $charge->created);
                }
            }
            if (!$paidAt && $session->payment_intent) {
                $paidAt = date('c', $session->payment_intent->created ?? time());
            }
        } elseif ($session->payment_status === 'unpaid' && $session->status === 'expired') {
            $status = 'expired';
        }

        return [
            'status' => $status,
            'paid_at' => $paidAt,
            'amount' => $amount,
        ];
    }

    public function refundPayment(string $transactionId, float $amount): array
    {
        $session = \Stripe\Checkout\Session::retrieve($transactionId, ['expand' => ['payment_intent']]);
        $paymentIntentId = $session->payment_intent;
        if (is_object($paymentIntentId)) {
            $paymentIntentId = $paymentIntentId->id;
        }
        if (!$paymentIntentId) {
            throw new \RuntimeException('Sessão sem payment_intent para reembolso.');
        }

        $refund = \Stripe\Refund::create([
            'payment_intent' => $paymentIntentId,
            'amount' => (int) round($amount * 100),
            'reason' => 'requested_by_customer',
        ]);

        return [
            'refund_id' => $refund->id,
            'status' => $refund->status,
            'amount' => $amount,
        ];
    }

    /**
     * Processa webhook do Stripe (checkout.session.completed).
     * Valida assinatura com Stripe-Signature.
     */
    public function handleWebhook(Request $request): ?Payment
    {
        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');
        $webhookSecret = config('payment.gateway_config.stripe_webhook_secret');

        if (!$webhookSecret) {
            Log::warning('Stripe webhook secret not configured');
            return null;
        }

        try {
            $event = \Stripe\Webhook::constructEvent($payload, $sigHeader, $webhookSecret);
        } catch (\Stripe\Exception\SignatureVerificationException $e) {
            Log::warning('Stripe webhook signature invalid', ['error' => $e->getMessage()]);
            throw new \App\Exceptions\InvalidWebhookSignatureException('Assinatura Stripe inválida.', 401);
        }

        if ($event->type !== 'checkout.session.completed') {
            Log::info('Stripe webhook ignored', ['type' => $event->type]);
            return null;
        }

        $session = $event->data->object;
        $paymentId = $session->metadata->payment_id ?? null;
        if (!$paymentId) {
            Log::warning('Stripe webhook: checkout.session.completed without payment_id in metadata');
            return null;
        }

        $payment = Payment::find($paymentId);
        if (!$payment) {
            Log::warning('Stripe webhook: payment not found', ['payment_id' => $paymentId]);
            return null;
        }

        return $payment;
    }

    private function mapPaymentMethodToStripe(string $method): array
    {
        return match ($method) {
            'pix' => ['pix'],
            'credit_card' => ['card'],
            'boleto' => ['boleto'],
            default => ['card'],
        };
    }
}
