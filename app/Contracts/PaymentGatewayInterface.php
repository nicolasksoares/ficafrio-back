<?php

namespace App\Contracts;

use App\Models\Payment;
use Illuminate\Http\Request;

interface PaymentGatewayInterface
{
    /**
     * Cria um pagamento no gateway
     *
     * @param Payment $payment
     * @param string $method (pix, credit_card, boleto)
     * @return array ['transaction_id', 'payment_url', 'payment_code', 'status', ...]
     */
    public function createPayment(Payment $payment, string $method): array;

    /**
     * Consulta o status de um pagamento no gateway
     *
     * @param string $transactionId
     * @return array ['status', 'paid_at', 'amount', ...]
     */
    public function getPaymentStatus(string $transactionId): array;

    /**
     * Reembolsa um pagamento
     *
     * @param string $transactionId
     * @param float $amount
     * @return array ['refund_id', 'status', 'amount', ...]
     */
    public function refundPayment(string $transactionId, float $amount): array;

    /**
     * Processa webhook do gateway
     *
     * @param Request $request
     * @return Payment|null
     */
    public function handleWebhook(Request $request): ?Payment;
}

