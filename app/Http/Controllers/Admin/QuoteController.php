<?php

namespace App\Http\Controllers\Admin;

use App\Enums\QuoteStatus;
use App\Http\Controllers\Controller;
use App\Http\Resources\AdminQuoteResource;
use App\Models\Quote;
use App\Models\QuoteAudit;
use App\Models\QuoteHistory;
use App\Notifications\QuoteStatusChanged;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class QuoteController extends Controller
{
    public function index(Request $request)
    {
        $query = Quote::query();

        if ($request->has('status')) {
            $statuses = array_map('trim', explode(',', $request->status));
            $statuses = array_filter($statuses);

            if (in_array(QuoteStatus::Rejeitado->value, $statuses)) {
                $query->withTrashed();
            }

            if (! empty($statuses)) {
                $query->whereIn('status', $statuses);
            }
        } else {
            $query->where('status', QuoteStatus::EmAnaliseAdmin);
        }

        $query->with(['space.company', 'storageRequest.company', 'histories.company', 'payment'])
            ->orderBy('created_at', 'desc');

        $quotes = $query->paginate(15);

        return AdminQuoteResource::collection($quotes);
    }

    public function approve(Request $request, $id)
    {
        $request->validate([
            'approved_price' => 'nullable|numeric|min:0.01',
        ]);

        $quote = Quote::with(['space.company', 'storageRequest.company'])->findOrFail($id);

        if ($quote->status !== QuoteStatus::EmAnaliseAdmin) {
            return response()->json(['message' => 'Esta proposta não está aguardando aprovação.'], 422);
        }

        if ($quote->valid_until && Carbon::parse($quote->valid_until)->isPast()) {
            return response()->json(['message' => 'Esta proposta expirou. Não é possível aprovar.'], 422);
        }

        return DB::transaction(function () use ($request, $quote) {
            $quote = Quote::where('id', $quote->id)->lockForUpdate()->first();
            $quote->load(['space.company', 'storageRequest.company']);

            if ($quote->status !== QuoteStatus::EmAnaliseAdmin) {
                return response()->json(['message' => 'Esta proposta já foi processada.'], 422);
            }

            if (!$quote->price || (float) $quote->price <= 0) {
                return response()->json(['message' => 'Proposta sem valor válido. Não é possível aprovar.'], 422);
            }

            $oldPrice = (float) $quote->price;
            $newPrice = $request->filled('approved_price')
                ? (float) $request->approved_price
                : $oldPrice;

            $quote->update([
                'status' => QuoteStatus::Respondido,
                'price' => $newPrice,
                'admin_approved_at' => now(),
                'admin_approved_by' => $request->user()->id,
            ]);

            QuoteAudit::create([
                'quote_id' => $quote->id,
                'admin_id' => $request->user()->id,
                'action' => 'approve',
                'old_status' => QuoteStatus::EmAnaliseAdmin->value,
                'new_status' => QuoteStatus::Respondido->value,
                'old_price' => $oldPrice,
                'new_price' => $newPrice,
            ]);

            QuoteHistory::create([
                'quote_id' => $quote->id,
                'company_id' => $request->user()->id,
                'action' => 'respondido',
                'description' => 'Aprovado pela plataforma. Orçamento: R$ ' . number_format($newPrice, 2, ',', '.'),
            ]);

            $quote->storageRequest->company->notify(new QuoteStatusChanged(
                'Sua proposta foi aprovada! Você já pode aceitar o orçamento.',
                'respondido',
                $quote->fresh()
            ));

            $quote->load('histories.company');

            return new AdminQuoteResource($quote);
        });
    }

    public function reject(Request $request, $id)
    {
        $request->validate([
            'reason' => 'nullable|string|max:1000',
        ]);

        $quote = Quote::with(['space.company', 'storageRequest.company'])->findOrFail($id);

        if ($quote->status !== QuoteStatus::EmAnaliseAdmin) {
            return response()->json(['message' => 'Esta proposta não está aguardando aprovação.'], 422);
        }

        return DB::transaction(function () use ($request, $quote) {
            $quote = Quote::where('id', $quote->id)->lockForUpdate()->first();
            $quote->load(['space.company', 'storageRequest.company']);

            if ($quote->status !== QuoteStatus::EmAnaliseAdmin) {
                return response()->json(['message' => 'Esta proposta já foi processada.'], 422);
            }

            $oldPrice = (float) $quote->price;

            QuoteAudit::create([
                'quote_id' => $quote->id,
                'admin_id' => $request->user()->id,
                'action' => 'reject',
                'old_status' => QuoteStatus::EmAnaliseAdmin->value,
                'new_status' => QuoteStatus::Rejeitado->value,
                'old_price' => $oldPrice,
                'new_price' => null,
                'reason' => $request->reason,
            ]);

            $quote->update([
                'status' => QuoteStatus::Rejeitado,
                'rejection_reason' => $request->reason ?? 'Recusado pela plataforma.',
            ]);

            QuoteHistory::create([
                'quote_id' => $quote->id,
                'company_id' => $request->user()->id,
                'action' => 'rejeitado',
                'description' => 'Recusado pela plataforma.' . ($request->reason ? " Motivo: {$request->reason}" : ''),
            ]);

            $quote->space->company->notify(new QuoteStatusChanged(
                'Sua proposta foi recusada pela plataforma.',
                'rejeitado',
                $quote
            ));

            $quote->delete();

            return response()->json(['message' => 'Proposta rejeitada com sucesso.'], 200);
        });
    }
}
