<?php

namespace App\Http\Controllers;

use App\Http\Resources\PaymentResource;
use App\Http\Requests\ProcessPaymentRequest;
use App\Models\Payment;
use App\Models\Quote;
use App\Services\PaymentService;
use App\Services\WebhookValidationService;
use App\Exceptions\PaymentNotFoundException;
use App\Exceptions\PaymentAlreadyExistsException;
use App\Exceptions\PaymentCannotBeProcessedException;
use App\Exceptions\InvalidWebhookSignatureException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;

class PaymentController extends Controller
{
    protected PaymentService $paymentService;
    protected WebhookValidationService $webhookValidator;

    public function __construct(PaymentService $paymentService, WebhookValidationService $webhookValidator)
    {
        $this->paymentService = $paymentService;
        $this->webhookValidator = $webhookValidator;
    }

    /**
     * Lista pagamentos do usuário autenticado
     */
    public function index(Request $request)
    {
        $user = $request->user();
        
        $payments = Payment::where('company_id', $user->id)
            ->orWhere('space_owner_id', $user->id)
            ->with(['quote', 'payer', 'spaceOwner'])
            ->latest()
            ->paginate(15);

        return PaymentResource::collection($payments);
    }

    /**
     * Detalhes de um pagamento específico
     */
    public function show(Request $request, $id)
    {
        $payment = Payment::with(['quote', 'payer', 'spaceOwner'])->findOrFail($id);
        $user = $request->user();

        if (Gate::denies('view', $payment)) {
            return response()->json(['message' => 'Acesso negado.'], 403);
        }

        return new PaymentResource($payment);
    }

    /**
     * Cria pagamento para uma Quote (se não existir)
     */
    public function create(Request $request, $quoteId)
    {
        $quote = Quote::with(['storageRequest', 'space'])->findOrFail($quoteId);
        $user = $request->user();

        // Verifica se usuário é dono da solicitação
        if ($quote->storageRequest->company_id !== $user->id) {
            return response()->json(['message' => 'Acesso negado.'], 403);
        }

        // Verifica se já existe pagamento
        if ($quote->hasPayment()) {
            return new PaymentResource($quote->payment);
        }

        // Verifica se pode criar pagamento
        if (!$quote->canCreatePayment()) {
            return response()->json([
                'message' => 'Não é possível criar pagamento para esta Quote.',
            ], 422);
        }

        try {
            $payment = $this->paymentService->createPayment($quote);
            return new PaymentResource($payment);
        } catch (PaymentAlreadyExistsException | PaymentCannotBeProcessedException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], $e->getCode());
        } catch (\Exception $e) {
            Log::error('Error creating payment', [
                'quote_id' => $quoteId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Erro ao criar pagamento.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Processa pagamento (escolhe método e integra com gateway)
     */
    public function process(ProcessPaymentRequest $request, $id)
    {
        $payment = Payment::findOrFail($id);
        $user = $request->user();

        if (Gate::denies('process', $payment)) {
            return response()->json(['message' => 'Acesso negado.'], 403);
        }

        try {
            $payment = $this->paymentService->processPayment(
                $payment,
                $request->validated()['payment_method']
            );

            return new PaymentResource($payment);
        } catch (PaymentCannotBeProcessedException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], $e->getCode());
        } catch (\Exception $e) {
            $message = $e->getMessage();
            $isStripeInvalidPaymentMethod = str_contains($message, 'payment method') && (str_contains($message, 'invalid') || str_contains($message, 'is invalid'));

            if ($isStripeInvalidPaymentMethod) {
                Log::warning('Stripe payment method not enabled', [
                    'payment_id' => $id,
                    'error' => $message,
                ]);
                return response()->json([
                    'message' => 'Este método de pagamento não está ativo na sua conta Stripe. Ative em Configurações → Métodos de pagamento no Dashboard Stripe (dashboard.stripe.com), ou use Cartão de crédito / Boleto.',
                ], 422);
            }

            Log::error('Error processing payment', [
                'payment_id' => $id,
                'error' => $message,
            ]);

            $payment = Payment::find($id);
            if ($payment) {
                try {
                    $this->paymentService->markPaymentAsFailed($payment);
                } catch (\Throwable $markFailedError) {
                    Log::warning('Could not mark payment as failed', [
                        'payment_id' => $id,
                        'error' => $markFailedError->getMessage(),
                    ]);
                }
            }

            return response()->json([
                'message' => 'Erro ao processar pagamento.',
                'error' => $message,
            ], 500);
        }
    }

    /**
     * Consulta status do pagamento
     */
    public function status($id)
    {
        $payment = Payment::findOrFail($id);

        // Se está processando, consulta gateway (quando gateway real for implementado)
        // Por enquanto, apenas retorna o status atual

        return new PaymentResource($payment->fresh());
    }

    /**
     * Webhook para receber notificações do gateway
     */
    public function webhook(Request $request)
    {
        Log::info('Payment webhook received', [
            'payload' => $request->all(),
            'headers' => $request->headers->all(),
            'ip' => $request->ip(),
        ]);

        try {
            // Gateway stripe: validação é feita no StripeGatewayService::handleWebhook (Stripe-Signature + Webhook::constructEvent).
            // Outros gateways: usar WebhookValidationService (X-Signature + HMAC).
            if (config('payment.gateway') !== 'stripe') {
                $this->webhookValidator->validateSignature($request);
                $this->webhookValidator->validateTimestamp($request);
            }

            // Processa webhook via PaymentService
            $payment = $this->paymentService->processWebhook($request);

            if ($payment) {
                Log::info('Webhook processed successfully', [
                    'payment_id' => $payment->id,
                ]);

                return response()->json([
                    'message' => 'Webhook processado com sucesso.',
                    'payment_id' => $payment->id,
                ]);
            }

            return response()->json(['message' => 'Webhook recebido.'], 200);
        } catch (InvalidWebhookSignatureException $e) {
            Log::warning('Webhook signature validation failed', [
                'error' => $e->getMessage(),
                'ip' => $request->ip(),
            ]);

            return response()->json([
                'message' => $e->getMessage(),
            ], $e->getCode());
        } catch (\Exception $e) {
            Log::error('Error processing webhook', [
                'error' => $e->getMessage(),
                'payload' => $request->all(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'message' => 'Erro ao processar webhook.',
            ], 500);
        }
    }
}

