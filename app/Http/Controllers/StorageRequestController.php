<?php

namespace App\Http\Controllers;

use App\Enums\ProductType;
use App\Enums\RequestStatus;
use App\Enums\UnitType;
use App\Http\Resources\StorageRequestResource;
use App\Http\Resources\SpaceResource; // Adicionado para formatar os matches
use App\Models\StorageRequest;
use App\Models\Space;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Enum;

class StorageRequestController extends Controller
{
    public function index(Request $request)
    {
        /** @var \App\Models\Company $user */
        $user = $request->user();
        
        $requests = $user
            ->storageRequests()
            ->latest()
            ->paginate(10);

        return StorageRequestResource::collection($requests);
    }

    public function store(Request $request)
    {
        /** @var \App\Models\Company $user */
        $user = $request->user();

        // Verifica permissão (Policy)
        if ($user->cannot('create', StorageRequest::class)) {
            return response()->json(['message' => 'Apenas clientes podem solicitar armazenagem.'], 403);
        }

        // Validação completa (Incluindo campos que faltavam no seu snippet original)
        $data = $request->validate([
            'title' => 'nullable|string|max:255',
            'category' => 'nullable|string', // ex: resfriados/congelados
            'product_type' => ['required', new Enum(ProductType::class)],
            'description' => 'nullable|string',
            'quantity' => 'required|integer|min:1',
            // Aceita string ou Enum para unit, para evitar conflitos se o Enum não estiver 100%
            'unit' => ['required'], 
            'temp_min' => 'required|integer',
            'temp_max' => 'required|integer|gte:temp_min',
            'target_city' => 'required|string',
            'target_state' => 'required|string|size:2',
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'required|date|after:start_date',
            'proposed_price' => 'nullable|numeric',
            'contact_name' => 'nullable|string',
            'contact_phone' => 'nullable|string',
            'contact_email' => 'nullable|email',
            'requester_message' => 'nullable|string'
        ]);

        // Preenche campos opcionais com dados do usuário se não fornecidos
        if (empty($data['title'])) {
            try {
                $productType = ProductType::from($data['product_type']);
                $title = "Armazenagem de {$productType->label()} - {$data['target_city']}/{$data['target_state']}";
                $data['title'] = \App\Helpers\SanitizeHelper::title($title);
            } catch (\ValueError $e) {
                $title = "Armazenagem - {$data['target_city']}/{$data['target_state']}";
                $data['title'] = \App\Helpers\SanitizeHelper::title($title);
            }
        }

        // Sanitiza campos de texto
        if (isset($data['description'])) {
            $data['description'] = \App\Helpers\SanitizeHelper::description($data['description']);
        }
        if (isset($data['requester_message'])) {
            $data['requester_message'] = \App\Helpers\SanitizeHelper::longText($data['requester_message'] ?? '', 2000);
        }
        if (isset($data['title'])) {
            $data['title'] = \App\Helpers\SanitizeHelper::title($data['title']);
        }

        if (empty($data['contact_name'])) {
            $data['contact_name'] = $user->trade_name ?? 'Não informado';
        }

        if (empty($data['contact_phone'])) {
            $data['contact_phone'] = $user->phone ?? '';
        }

        if (empty($data['contact_email'])) {
            $data['contact_email'] = $user->email ?? '';
        }

        // Valida se email foi preenchido (obrigatório)
        if (empty($data['contact_email'])) {
            return response()->json([
                'message' => 'Email de contato é obrigatório. Complete seu perfil.',
                'errors' => [
                    'contact_email' => ['Email de contato é obrigatório. Complete seu perfil.']
                ]
            ], 422);
        }

        // Define status inicial
        $data['status'] = RequestStatus::Pendente;

        // Cria vinculado à empresa do usuário
        $storageRequest = $user->storageRequests()->create($data);

        return new StorageRequestResource($storageRequest);
    }

    public function update(Request $request, $id)
    {
        $storageRequest = StorageRequest::findOrFail($id);

        if ($request->user()->cannot('delete', $storageRequest)) {
            return response()->json(['message' => 'Acesso proibido'], 403);
        }

        if ($storageRequest->status !== RequestStatus::Pendente) {
            return response()->json(['message' => 'Não é possível editar uma solicitação em andamento/finalizada.'], 422);
        }

        $data = $request->validate([
            'title' => 'sometimes|string|max:255',
            'product_type' => ['sometimes', new Enum(ProductType::class)],
            'description' => 'nullable|string',
            'quantity' => 'sometimes|integer|min:1',
            'unit' => ['sometimes'],
            'temp_min' => 'sometimes|integer',
            'temp_max' => 'sometimes|integer|gte:temp_min',
            'target_city' => 'sometimes|string',
            'target_state' => 'sometimes|string|size:2',
            'start_date' => 'sometimes|date|after_or_equal:today',
            'end_date' => 'sometimes|date|after:start_date',
            'proposed_price' => 'nullable|numeric',
            'contact_name' => 'sometimes|string',
            'contact_phone' => 'sometimes|string',
            'contact_email' => 'sometimes|email',
        ]);

        // Garante que o status não seja alterado manualmente aqui
        unset($data['status']);

        $storageRequest->update($data);

        return new StorageRequestResource($storageRequest);
    }

    public function destroy(Request $request, $id)
    {
        $storageRequest = StorageRequest::findOrFail($id);

        if ($request->user()->cannot('delete', $storageRequest)) {
            return response()->json(['message' => 'Acesso proibido'], 403);
        }

        $storageRequest->delete();

        return response()->json(null, 204);
    }

    /**
     * Busca espaços compatíveis com a solicitação.
     */
    public function matches($id)
    {
        $storageRequest = StorageRequest::findOrFail($id);

        // 1. Carrega 'photos' para evitar Lazy Loading Violation
        // 2. Filtra por Cidade/Estado/Temperatura/Capacidade
        $matches = Space::with('photos')
            ->where('city', $storageRequest->target_city)
            ->where('state', $storageRequest->target_state)
            ->where('min_temperature_celsius', '<=', $storageRequest->temp_max)
            ->where('max_temperature_celsius', '>=', $storageRequest->temp_min)
            ->where('available_pallet_positions', '>=', $storageRequest->quantity)
            ->get();

        // Usa o SpaceResource para garantir que as URLs das fotos venham corretas
        return SpaceResource::collection($matches);
    }
}