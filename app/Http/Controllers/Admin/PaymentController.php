<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\PaymentResource;
use App\Http\Requests\RefundPaymentRequest;
use App\Models\Payment;
use App\Services\PaymentService;
use App\Exceptions\PaymentCannotBeRefundedException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class PaymentController extends Controller
{
    protected PaymentService $paymentService;

    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    /**
     * Lista todos os pagamentos (admin)
     */
    public function index(Request $request)
    {
        $query = Payment::with(['quote', 'payer', 'spaceOwner']);

        // Filtros
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('payment_method')) {
            $query->where('payment_method', $request->payment_method);
        }

        if ($request->has('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->has('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        $payments = $query->latest()->paginate(20);

        return PaymentResource::collection($payments);
    }

    /**
     * Detalhes completos de um pagamento (admin)
     */
    public function show($id)
    {
        $payment = Payment::with(['quote', 'payer', 'spaceOwner'])->findOrFail($id);
        return new PaymentResource($payment);
    }

    /**
     * Força transferência manual para dono do espaço
     */
    public function transfer(Request $request, $id)
    {
        $payment = Payment::findOrFail($id);
        $user = $request->user();

        if (Gate::denies('transfer', $payment)) {
            return response()->json(['message' => 'Acesso negado.'], 403);
        }

        if ($payment->status->value !== 'paid') {
            return response()->json([
                'message' => 'Apenas pagamentos confirmados podem ser transferidos.',
            ], 422);
        }

        try {
            $this->paymentService->transferToSpaceOwner($payment);

            return response()->json([
                'message' => 'Transferência processada com sucesso.',
            ]);
        } catch (\Exception $e) {
            Log::error('Error transferring payment', [
                'payment_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Erro ao processar transferência.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Reembolsa um pagamento
     */
    public function refund(RefundPaymentRequest $request, $id)
    {
        $payment = Payment::findOrFail($id);
        $user = $request->user();

        if (Gate::denies('refund', $payment)) {
            return response()->json(['message' => 'Acesso negado.'], 403);
        }

        try {
            $payment = $this->paymentService->refundPayment(
                $payment,
                $request->validated()['reason'] ?? null
            );

            return new PaymentResource($payment);
        } catch (PaymentCannotBeRefundedException $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], $e->getCode());
        } catch (\Exception $e) {
            Log::error('Error refunding payment', [
                'payment_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Erro ao processar reembolso.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Estatísticas de pagamentos
     */
    public function stats(Request $request)
    {
        $user = $request->user();

        if (Gate::denies('viewStats', Payment::class)) {
            return response()->json(['message' => 'Acesso negado.'], 403);
        }
        $totalPayments = Payment::count();
        $paidPayments = Payment::paid()->count();
        $pendingPayments = Payment::pending()->count();
        $processingPayments = Payment::processing()->count();
        $failedPayments = Payment::failed()->count();

        $totalAmount = Payment::paid()->sum('amount');
        $totalFees = Payment::paid()->sum('platform_fee');
        $totalNetAmount = Payment::paid()->sum('net_amount');

        // Usar DB::table() para evitar problemas com accessors do modelo
        $byMethod = DB::table('payments')
            ->selectRaw('payment_method, COUNT(*) as count, SUM(amount) as total')
            ->whereNotNull('payment_method')
            ->whereNull('deleted_at')
            ->groupBy('payment_method')
            ->get()
            ->map(function ($item) {
                return [
                    'payment_method' => $item->payment_method,
                    'count' => (int) $item->count,
                    'total' => (float) $item->total,
                ];
            });

        $byStatus = DB::table('payments')
            ->selectRaw('status, COUNT(*) as count')
            ->whereNull('deleted_at')
            ->groupBy('status')
            ->get()
            ->map(function ($item) {
                return [
                    'status' => $item->status,
                    'count' => (int) $item->count,
                ];
            });

        return response()->json([
            'overview' => [
                'total_payments' => $totalPayments,
                'paid' => $paidPayments,
                'pending' => $pendingPayments,
                'processing' => $processingPayments,
                'failed' => $failedPayments,
            ],
            'financial' => [
                'total_amount' => (float) $totalAmount,
                'total_fees' => (float) $totalFees,
                'total_net_amount' => (float) $totalNetAmount,
            ],
            'by_method' => $byMethod,
            'by_status' => $byStatus,
        ]);
    }
}

