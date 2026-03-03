<?php

namespace App\Services;

use App\Contracts\PaymentGatewayInterface;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Implementação base/stub do gateway de pagamento
 * 
 * Esta classe retorna estruturas padrão para permitir desenvolvimento
 * sem gateway real. Quando um gateway real for escolhido, criar uma
 * implementação específica (ex: AsaasGatewayService, MercadoPagoGatewayService)
 */
class PaymentGatewayService implements PaymentGatewayInterface
{
    public function createPayment(Payment $payment, string $method): array
    {
        Log::info('PaymentGatewayService: createPayment (stub)', [
            'payment_id' => $payment->id,
            'method' => $method,
        ]);

        // Estrutura padrão de resposta
        // Quando gateway real for integrado, substituir por chamada real à API
        return [
            'transaction_id' => 'stub_' . $payment->id . '_' . time(),
            'payment_url' => $method === 'pix' || $method === 'boleto' 
                ? route('payment.show', $payment->id) 
                : null,
            'payment_code' => $method === 'pix' 
                ? $this->generatePixCode($payment)
                : ($method === 'boleto' ? $this->generateBoletoCode($payment) : null),
            'status' => 'processing',
            'expires_at' => now()->addDays(
                config("payment.expiration_days.{$method}", 1)
            )->toIso8601String(),
        ];
    }

    public function getPaymentStatus(string $transactionId): array
    {
        Log::info('PaymentGatewayService: getPaymentStatus (stub)', [
            'transaction_id' => $transactionId,
        ]);

        // Em produção, consultar status real no gateway
        return [
            'status' => 'processing',
            'paid_at' => null,
            'amount' => 0,
        ];
    }

    public function refundPayment(string $transactionId, float $amount): array
    {
        Log::info('PaymentGatewayService: refundPayment (stub)', [
            'transaction_id' => $transactionId,
            'amount' => $amount,
        ]);

        // Em produção, processar reembolso real no gateway
        return [
            'refund_id' => 'stub_refund_' . time(),
            'status' => 'refunded',
            'amount' => $amount,
        ];
    }

    public function handleWebhook(Request $request): ?Payment
    {
        Log::info('PaymentGatewayService: handleWebhook (stub)', [
            'payload' => $request->all(),
        ]);

        // Em produção, processar webhook real do gateway
        // Validar assinatura, extrair dados, atualizar Payment
        return null;
    }

    /**
     * Gera código PIX fictício (stub)
     */
    private function generatePixCode(Payment $payment): string
    {
        // Em produção, usar API do gateway para gerar código PIX real
        return '00020126360014BR.GOV.BCB.PIX0114+5511999999999020400005303986540' . 
               number_format($payment->amount, 2, '', '') . 
               '5802BR5925FICA FRIO LTDA6009SAO PAULO62070503***6304' . 
               strtoupper(substr(md5($payment->id), 0, 4));
    }

    /**
     * Gera linha digitável de boleto fictícia (stub)
     */
    private function generateBoletoCode(Payment $payment): string
    {
        // Em produção, usar API do gateway para gerar boleto real
        return '34191' . str_pad($payment->id, 10, '0', STR_PAD_LEFT) . 
               '1' . str_pad((int)($payment->amount * 100), 10, '0', STR_PAD_LEFT) . 
               '9' . date('dmy');
    }
}

