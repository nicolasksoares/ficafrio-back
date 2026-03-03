<?php

namespace App\Http\Controllers;

use App\Enums\QuoteStatus;
use App\Http\Resources\QuoteResource;
use App\Models\Quote;
use App\Models\Space;
use App\Models\StorageRequest;
use App\Models\QuoteHistory;
use App\Services\OccupationService;
use App\Services\PaymentService;
use App\Notifications\QuoteStatusChanged;
use App\Notifications\QuotePendingAdminReviewNotification;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class QuoteController extends Controller
{
    protected $occupationService;
    protected $paymentService;

    public function __construct(OccupationService $occupationService, PaymentService $paymentService)
    {
        $this->occupationService = $occupationService;
        $this->paymentService = $paymentService;
    }

    public function index(Request $request)
    {
        $user = $request->user();
        $quotes = Quote::whereHas('storageRequest', fn($q) => $q->where('company_id', $user->id))
            ->orWhereHas('space', fn($q) => $q->where('company_id', $user->id))
            // CORREÇÃO AQUI: Adicionado 'histories.company' e 'payment' para evitar Lazy Loading Violation
            ->with(['space.company', 'storageRequest.company', 'histories.company', 'payment'])
            ->latest()->paginate(10);

        return QuoteResource::collection($quotes);
    }

    public function store(Request $request)
    {
        $user = $request->user();
        $data = $request->validate([
            'storage_request_id' => 'required|exists:storage_requests,id',
            'space_id' => 'required|exists:spaces,id',
        ]);

        $storageRequest = StorageRequest::findOrFail($data['storage_request_id']);
        $space = Space::with('company')->findOrFail($data['space_id']);

        if (!$this->occupationService->hasSpaceAvailable($space, $storageRequest->quantity, $storageRequest->start_date, $storageRequest->end_date)) {
            return response()->json(['message' => 'Espaço lotado para este período.'], 422);
        }

        return DB::transaction(function () use ($storageRequest, $space, $user) {
            $quote = Quote::firstOrCreate(
                ['storage_request_id' => $storageRequest->id, 'space_id' => $space->id],
                ['status' => QuoteStatus::Solicitado]
            );

            QuoteHistory::create([
                'quote_id' => $quote->id, 
                'company_id' => $user->id, 
                'action' => 'solicitado',
                'description' => "{$user->trade_name} solicitou cotação para {$storageRequest->quantity} paletes."
            ]);

            $space->company->notify(new QuoteStatusChanged("Nova solicitação de cotação recebida!", 'solicitado', $quote));

            // Carrega relações para retorno seguro se decidir usar Resource futuramente
            $quote->load(['space.company', 'storageRequest.company', 'histories.company']);

            return response()->json($quote, 201);
        });
    }

    public function update(Request $request, $id) 
    {
        // Carregamos as relações base necessárias
        $quote = Quote::with(['space.company', 'storageRequest.company'])->findOrFail($id);
        $user = $request->user();

        if (Gate::denies('manage-quote', $quote)) {
            return response()->json(['message' => 'Acesso negado.'], 403);
        }

        return DB::transaction(function () use ($request, $quote, $user) {
            $oldStatus = $quote->status;

            // --- LÓGICA DO DONO DO ESPAÇO (PARCEIRO) ---
            if ($user->id === $quote->space->company_id) {
                if ($request->status === 'rejeitado') {
                    // Estorno de inventário se já estava aceito
                    if ($oldStatus === QuoteStatus::Aceito) {
                        $quote->space()->lockForUpdate()->first()->increment('available_pallet_positions', $quote->storageRequest->quantity);
                    }

                    $quote->update([
                        'status' => QuoteStatus::Rejeitado, 
                        'rejection_reason' => $request->reason ?? 'Recusado pelo parceiro.'
                    ]);
                    
                    QuoteHistory::create([
                        'quote_id' => $quote->id, 'company_id' => $user->id, 'action' => 'rejeitado', 
                        'description' => "O parceiro recusou a oferta. Vagas devolvidas ao estoque (se aplicável)."
                    ]);
                    
                    $quote->storageRequest->company->notify(new QuoteStatusChanged("Sua solicitação foi recusada.", 'rejeitado', $quote));
                    
                    // CORREÇÃO: Recarregar histories.company antes de retornar o Resource
                    $quote->load('histories.company');
                    return new QuoteResource($quote);
                }

                $data = $request->validate([
                    'price' => 'required|numeric|min:0.01',
                    'valid_until' => 'required|date|after:today'
                ]);

                $quote->update(array_merge($data, ['status' => QuoteStatus::EmAnaliseAdmin]));

                QuoteHistory::create([
                    'quote_id' => $quote->id, 'company_id' => $user->id, 'action' => 'em_analise_admin',
                    'description' => "Orçamento enviado: R$ " . number_format($data['price'], 2, ',', '.') . ". Aguardando aprovação da plataforma."
                ]);

                $this->notifyAdminsOfPendingQuote($quote);
            }

            // --- LÓGICA DO CLIENTE (FECHAMENTO E DEDUÇÃO DE INVENTÁRIO) ---
            if ($user->id === $quote->storageRequest->company_id && $request->status === 'aceito') {
                if ($quote->status === QuoteStatus::EmAnaliseAdmin) {
                    return response()->json(['message' => 'Esta proposta ainda está em análise pela plataforma. Aguarde a aprovação.'], 422);
                }

                if ($quote->status !== QuoteStatus::Respondido) {
                    return response()->json(['message' => 'Esta proposta não está disponível para aceite.'], 422);
                }

                if (!$quote->price || (float) $quote->price <= 0) {
                    return response()->json(['message' => 'Não é possível aceitar uma proposta sem preço definido.'], 422);
                }

                // Lock preventivo no banco
                $space = Space::where('id', $quote->space_id)->lockForUpdate()->first();

                if ($quote->valid_until && Carbon::parse($quote->valid_until)->isPast()) {
                    return response()->json(['message' => 'Este orçamento expirou.'], 422);
                }

                if ($oldStatus !== QuoteStatus::Aceito) {
                    if ($space->available_pallet_positions < $quote->storageRequest->quantity) {
                        return response()->json(['message' => 'Espaço insuficiente no momento.'], 422);
                    }

                    // Dedução atômica
                    $space->decrement('available_pallet_positions', $quote->storageRequest->quantity);
                }

                $quote->update(['status' => QuoteStatus::Aceito]);

                // Invalida outras Quotes da mesma StorageRequest (soft delete)
                Quote::where('storage_request_id', $quote->storage_request_id)
                    ->where('id', '!=', $quote->id)
                    ->whereIn('status', [QuoteStatus::Respondido, QuoteStatus::EmAnaliseAdmin])
                    ->delete();

                QuoteHistory::create([
                    'quote_id' => $quote->id, 'company_id' => $user->id, 'action' => 'aceito',
                    'description' => "Negócio fechado! {$quote->storageRequest->quantity} paletes reservados."
                ]);

                $quote->space->company->notify(new QuoteStatusChanged("Seu orçamento foi aceito!", 'aceito', $quote));

                // Cria pagamento automaticamente quando Quote é aceita
                try {
                    if ($quote->canCreatePayment()) {
                        $this->paymentService->createPayment($quote);
                    }
                } catch (\Exception $e) {
                    // Log erro mas não quebra o fluxo
                    Log::error('Error creating payment for accepted quote', [
                        'quote_id' => $quote->id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }

            // CORREÇÃO: Recarregar histories.company para o Resource não quebrar
            $quote->load('histories.company');

            return new QuoteResource($quote);
        });
    }

    public function offer(Request $request)
    {
        $user = $request->user();
        
        $data = $request->validate([
            'storage_request_id' => 'required|exists:storage_requests,id',
            'space_id' => 'required|exists:spaces,id',
            'price' => 'required|numeric|min:0.01',
            'valid_until' => 'required|date|after:today',
        ]);

        $storageRequest = StorageRequest::findOrFail($data['storage_request_id']);

        if (!$storageRequest->is_active) {
            return response()->json(['message' => 'Esta demanda já foi encerrada.'], 422);
        }

        $space = Space::where('id', $data['space_id'])
                      ->where('company_id', $user->id)
                      ->firstOrFail();

        // Evita duplicidade de oferta para o mesmo par Espaço-Demanda
        $alreadyExists = Quote::where('storage_request_id', $storageRequest->id)
                              ->where('space_id', $space->id)
                              ->exists();

        if ($alreadyExists) {
            return response()->json(['message' => 'Você já enviou uma oferta para esta demanda.'], 422);
        }

        return DB::transaction(function () use ($data, $space, $user, $storageRequest) {
            $quote = Quote::create([
                'storage_request_id' => $data['storage_request_id'],
                'space_id' => $space->id,
                'price' => $data['price'],
                'valid_until' => $data['valid_until'],
                'status' => QuoteStatus::EmAnaliseAdmin
            ]);

            QuoteHistory::create([
                'quote_id' => $quote->id,
                'company_id' => $user->id,
                'action' => 'em_analise_admin',
                'description' => "{$user->trade_name} ofereceu R$ " . number_format($data['price'], 2, ',', '.') . ". Aguardando aprovação da plataforma."
            ]);

            $this->notifyAdminsOfPendingQuote($quote);

            return response()->json($quote, 201);
        });
    }

    public function destroy(Request $request, $id)
    {
        $quote = Quote::with(['space.company', 'storageRequest.company'])->findOrFail($id);
        $user = $request->user();

        if ($user->id !== $quote->space->company_id) {
            return response()->json(['message' => 'Apenas o dono do espaço pode retirar a proposta.'], 403);
        }

        if ($quote->status !== QuoteStatus::EmAnaliseAdmin) {
            return response()->json(['message' => 'Só é possível retirar propostas em análise.'], 422);
        }

        $quote->update([
            'status' => QuoteStatus::Rejeitado,
            'rejection_reason' => 'Retirado pelo parceiro antes da aprovação.'
        ]);
        $quote->delete();

        QuoteHistory::create([
            'quote_id' => $quote->id,
            'company_id' => $user->id,
            'action' => 'retirado',
            'description' => "{$user->trade_name} retirou a proposta antes da aprovação."
        ]);

        return response()->json(null, 204);
    }

    protected function notifyAdminsOfPendingQuote(Quote $quote): void
    {
        $admins = \App\Models\Company::where('type', \App\Enums\UserType::Admin)->get();
        foreach ($admins as $admin) {
            $admin->notify(new QuotePendingAdminReviewNotification($quote));
        }
    }

    public function downloadContract($id)
    {
        $quote = Quote::with(['space.company', 'storageRequest.company'])->findOrFail($id);
        
        if ($quote->status->value !== 'aceito') {
            return response()->json(['message' => 'Contrato disponível apenas para propostas aceitas.'], 422);
        }

        $pdf = Pdf::loadView('pdf.contract', compact('quote'));
        return $pdf->download("contrato-ficafrio-{$quote->id}.pdf");
    }
}