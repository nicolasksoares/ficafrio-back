<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\Quote;
use App\Enums\PaymentStatus;
use App\Enums\PaymentMethod;
use App\Contracts\PaymentGatewayInterface;
use App\Notifications\PaymentCreated;
use App\Notifications\PaymentProcessing;
use App\Notifications\PaymentConfirmed;
use App\Notifications\PaymentFailed;
use App\Notifications\PaymentTransferred;
use App\Exceptions\PaymentCannotBeProcessedException;
use App\Exceptions\PaymentAlreadyExistsException;
use App\Exceptions\PaymentCannotBeRefundedException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class PaymentService
{
    protected PaymentGatewayInterface $gateway;

    public function __construct(PaymentGatewayInterface $gateway)
    {
        $this->gateway = $gateway;
    }

    /**
     * Cria um pagamento automaticamente quando Quote é aceita
     */
    public function createPayment(Quote $quote): Payment
    {
        if ($quote->hasPayment()) {
            throw new PaymentAlreadyExistsException();
        }

        if (!$quote->canCreatePayment()) {
            throw new PaymentCannotBeProcessedException('Não é possível criar pagamento para esta Quote.');
        }

        // Valida se Quote não expirou
        if ($quote->valid_until && Carbon::parse($quote->valid_until)->isPast()) {
            throw new PaymentCannotBeProcessedException('Esta cotação expirou. Não é possível criar pagamento.');
        }

        if (!$quote->price || $quote->price <= 0) {
            throw new PaymentCannotBeProcessedException('A cotação deve ter um valor válido para criar pagamento.');
        }

        return DB::transaction(function () use ($quote) {
            $amount = (float) $quote->price;
            $fee = $this->calculateFee($amount);
            $netAmount = $this->calculateNetAmount($amount, $fee);

            $payment = Payment::create([
                'quote_id' => $quote->id,
                'company_id' => $quote->storageRequest->company_id,
                'space_owner_id' => $quote->space->company_id,
                'amount' => $amount,
                'platform_fee' => $fee,
                'net_amount' => $netAmount,
                'status' => PaymentStatus::Pending,
            ]);

            // Atualiza Quote com payment_id
            $quote->update(['payment_id' => $payment->id]);

            // Notifica cliente
            $quote->storageRequest->company->notify(new PaymentCreated($payment));

            Log::info('Payment created', [
                'payment_id' => $payment->id,
                'quote_id' => $quote->id,
                'amount' => $amount,
                'fee' => $fee,
                'net_amount' => $netAmount,
            ]);

            return $payment;
        });
    }

    /**
     * Calcula taxa da plataforma (10% por padrão)
     */
    public function calculateFee(float $amount): float
    {
        $feePercentage = config('payment.platform_fee_percentage', 10);
        return round(($amount * $feePercentage) / 100, 2);
    }

    /**
     * Calcula valor líquido para dono do espaço
     */
    public function calculateNetAmount(float $amount, float $fee): float
    {
        return round($amount - $fee, 2);
    }

    /**
     * Processa pagamento (escolhe método e integra com gateway)
     */
    public function processPayment(Payment $payment, string $method): Payment
    {
        if (!$payment->canProcess()) {
            throw new PaymentCannotBeProcessedException(
                $payment->isExpired() 
                    ? 'Este pagamento expirou.' 
                    : 'Este pagamento não pode ser processado no momento.'
            );
        }

        if ($payment->amount <= 0) {
            throw new PaymentCannotBeProcessedException('Valor do pagamento inválido.');
        }

        // Idempotência: se já existe URL de pagamento (ex.: sessão Stripe ativa), retorna o payment atual
        if ($payment->payment_url && $payment->status === PaymentStatus::Processing) {
            return $payment->fresh();
        }

        return DB::transaction(function () use ($payment, $method) {
            $paymentMethod = PaymentMethod::from($method);
            $expirationDays = $paymentMethod->expirationDays();

            // Marca como processando
            $payment->markAsProcessing();
            $payment->update([
                'payment_method' => $paymentMethod,
                'expires_at' => $expirationDays > 0 
                    ? now()->addDays($expirationDays) 
                    : null,
            ]);

            // Integra com gateway
            try {
                $gatewayResponse = $this->gateway->createPayment($payment, $method);
                
                $payment->update([
                    'gateway' => config('payment.gateway', 'stub'),
                    'gateway_transaction_id' => $gatewayResponse['transaction_id'] ?? null,
                    'gateway_response' => $gatewayResponse,
                    'payment_url' => $gatewayResponse['payment_url'] ?? null,
                    'payment_code' => $gatewayResponse['payment_code'] ?? null,
                    'expires_at' => isset($gatewayResponse['expires_at']) 
                        ? Carbon::parse($gatewayResponse['expires_at']) 
                        : $payment->expires_at,
                ]);

                // Notifica que está processando
                $payment->payer->notify(new PaymentProcessing($payment));

                Log::info('Payment processed', [
                    'payment_id' => $payment->id,
                    'method' => $method,
                    'transaction_id' => $gatewayResponse['transaction_id'] ?? null,
                ]);

            } catch (\Exception $e) {
                Log::error('Payment processing failed', [
                    'payment_id' => $payment->id,
                    'error' => $e->getMessage(),
                ]);
                throw $e;
            }

            return $payment->fresh();
        });
    }

    /**
     * Marca pagamento como falho e notifica (fora de transação, para uso após rollback).
     */
    public function markPaymentAsFailed(Payment $payment): void
    {
        $payment->markAsFailed();
        $payment->payer->notify(new PaymentFailed($payment));
    }

    /**
     * Confirma pagamento (chamado via webhook ou consulta manual)
     */
    public function confirmPayment(Payment $payment, array $gatewayData = []): Payment
    {
        if ($payment->status === PaymentStatus::Paid) {
            return $payment; // Já está pago
        }

        return DB::transaction(function () use ($payment, $gatewayData) {
            $paidAt = isset($gatewayData['paid_at']) 
                ? Carbon::parse($gatewayData['paid_at']) 
                : now();

            $payment->markAsPaid($paidAt);
            
            if (!empty($gatewayData)) {
                $payment->update([
                    'gateway_response' => array_merge(
                        $payment->gateway_response ?? [],
                        $gatewayData
                    ),
                ]);
            }

            // Notifica ambas as partes
            $payment->payer->notify(new PaymentConfirmed($payment));
            $payment->spaceOwner->notify(new PaymentConfirmed($payment));

            // Transfere para dono do espaço
            $this->transferToSpaceOwner($payment);

            Log::info('Payment confirmed', [
                'payment_id' => $payment->id,
                'paid_at' => $paidAt,
            ]);

            return $payment->fresh();
        });
    }

    /**
     * Reembolsa um pagamento
     */
    public function refundPayment(Payment $payment, ?string $reason = null): Payment
    {
        if (!$payment->canRefund()) {
            throw new PaymentCannotBeRefundedException(
                $payment->status === PaymentStatus::Paid
                    ? 'Este pagamento não pode ser reembolsado.'
                    : 'Apenas pagamentos confirmados podem ser reembolsados.'
            );
        }

        return DB::transaction(function () use ($payment, $reason) {
            try {
                // Processa reembolso no gateway
                $refundData = $this->gateway->refundPayment(
                    $payment->gateway_transaction_id,
                    $payment->amount
                );

                $payment->markAsRefunded($reason);
                $payment->update([
                    'gateway_response' => array_merge(
                        $payment->gateway_response ?? [],
                        ['refund' => $refundData]
                    ),
                ]);

                // Estorna inventário do espaço
                $quote = $payment->quote;
                if ($quote && $quote->space) {
                    $quote->space->increment(
                        'available_pallet_positions',
                        $quote->storageRequest->quantity
                    );
                }

                Log::info('Payment refunded', [
                    'payment_id' => $payment->id,
                    'reason' => $reason,
                ]);

            } catch (\Exception $e) {
                Log::error('Payment refund failed', [
                    'payment_id' => $payment->id,
                    'error' => $e->getMessage(),
                ]);

                throw $e;
            }

            return $payment->fresh();
        });
    }

    /**
     * Transfere valor para dono do espaço (após confirmação)
     * 
     * Nota: Em produção, isso seria uma transferência real via gateway.
     * Por enquanto, apenas registra e notifica.
     */
    public function transferToSpaceOwner(Payment $payment): void
    {
        // Em produção, fazer transferência real via gateway
        // Por enquanto, apenas notifica que a transferência foi processada
        
        $payment->spaceOwner->notify(new PaymentTransferred($payment));

        Log::info('Payment transferred to space owner', [
            'payment_id' => $payment->id,
            'space_owner_id' => $payment->space_owner_id,
            'net_amount' => $payment->net_amount,
        ]);
    }

    /**
     * Processa webhook do gateway
     * 
     * @param \Illuminate\Http\Request $request
     * @return Payment|null
     */
    public function processWebhook(\Illuminate\Http\Request $request): ?Payment
    {
        $payment = $this->gateway->handleWebhook($request);
        if ($payment) {
            $this->confirmPayment($payment, []);
        }
        return $payment;
    }
}

