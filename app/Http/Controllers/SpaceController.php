<?php

namespace App\Http\Controllers;

use App\Http\Resources\SpaceResource;
use App\Models\Space;
use Illuminate\Http\Request;
use App\Http\Requests\StoreSpaceRequest;
use Illuminate\Support\Facades\Storage;
use App\Enums\SpaceStatus; 
use App\Enums\UserType;
use Illuminate\Validation\Rule;

class SpaceController extends Controller
{
    // BUSCA PÚBLICA (Buscar.tsx)
    // Correção: Só retorna o que for ATIVO e APROVADO.
    public function index(Request $request)
    {
        $query = Space::query()
            ->where('active', true)
            ->where('status', 'aprovado'); // Garante que só aprovados aparecem

        // Filtros (mantidos)
        if ($request->filled('type') && $request->type !== 'qualquer') {
            $query->where('type', $request->type);
        }
        if ($request->filled('city')) {
            $query->where('city', 'like', '%' . $request->city . '%');
        }
        if ($request->filled('state')) {
            $query->where('state', $request->state);
        }
        if ($request->filled('target_temp')) {
            $temp = $request->target_temp;
            $query->where('temp_min', '<=', $temp)->where('temp_max', '>=', $temp);
        }
        if ($request->filled('min_positions')) {
            $query->where('available_pallet_positions', '>=', $request->min_positions);
        }
        if ($request->filled('start_date')) {
            $query->whereDate('available_from', '<=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->whereDate('available_until', '>=', $request->end_date);
        }

        // Carrega todas as fotos para exibição no carrossel
        return SpaceResource::collection(
            $query->with('photos')->latest()->paginate(12)
        );
    }

    // MEUS ESPAÇOS (Cliente vê tudo: aprovado, pendente, rejeitado)
    public function mySpaces(Request $request)
    {
        return SpaceResource::collection($request->user()->spaces()->latest()->paginate(10));
    }

    // CRIAÇÃO (Sempre nasce Em Análise e Oculto)
    public function store(StoreSpaceRequest $request)
    {
        $user = $request->user();
        
        if ($user->type !== UserType::Cliente) {
            return response()->json(['message' => 'Apenas clientes podem criar espaços.'], 403);
        }
    
        // 1. Use apenas dados validados (StoreSpaceRequest mapeia street_address -> address etc.)
        $data = $request->validated();
        
        // Sanitiza campos de texto
        if (isset($data['name'])) {
            $data['name'] = \App\Helpers\SanitizeHelper::title($data['name']);
        }
        if (isset($data['description'])) {
            $data['description'] = \App\Helpers\SanitizeHelper::description($data['description']);
        }
        
        $data['status'] = SpaceStatus::EmAnalise; 
        $data['active'] = false;
        $data['available_from'] = $data['available_from'] ?? now()->toDateString();
        $data['available_until'] = $data['available_until'] ?? now()->addYear()->toDateString(); 
    
        // 2. Processe a imagem principal (primeira foto) com validação de MIME
        if ($request->hasFile('photo')) {
            $photo = $request->file('photo');
            // Validação adicional de MIME real
            $mimeValidator = new \App\Rules\ValidImageMime();
            if (!$mimeValidator->passes('photo', $photo)) {
                return response()->json([
                    'message' => 'O arquivo deve ser uma imagem válida (JPEG, PNG ou WebP).',
                ], 422);
            }
            $data['main_image'] = $photo->store('spaces', 'public');
        }
    
        // 3. Cria o espaço
        $space = $user->spaces()->create($data);
        
        // 4. Processa fotos adicionais (se houver)
        if ($request->hasFile('photos')) {
            $photos = $request->file('photos');
            // Garante que seja array mesmo se vier um único arquivo
            if (!is_array($photos)) {
                $photos = [$photos];
            }
            
            foreach ($photos as $photo) {
                if ($photo && $photo->isValid()) {
                    $path = $photo->store("spaces/{$space->id}", 'public');
                    $space->photos()->create(['path' => $path]);
                }
            }
        }
        
        // 5. Recarrega relacionamentos para retornar com fotos
        $space->load('photos');
        
        return new SpaceResource($space);
    }

    public function update(Request $request, $id)
    {
        $space = Space::findOrFail($id);
        
        if ($request->user()->cannot('update', $space)) {
            return response()->json(['message' => 'Acesso negado'], 403);
        }
    
        $request->validate([
            'name' => ['sometimes', Rule::unique('spaces')->ignore($space->id)->where(fn($q) => $q->where('company_id', $request->user()->id))],
            'temp_min' => 'sometimes|numeric',
            'temp_max' => 'sometimes|numeric|gte:temp_min',
            'capacity' => 'sometimes|integer',
            'available_pallet_positions' => 'sometimes|integer|lte:capacity',
            'available_until' => 'sometimes|date|after_or_equal:available_from',
            'photo' => 'sometimes|image|max:2048' 
        ]);
    
        $data = $request->except(['company_id', 'photo', 'photos', 'status', 'active']);
        
        // Sanitiza campos de texto
        if (isset($data['name'])) {
            $data['name'] = \App\Helpers\SanitizeHelper::title($data['name']);
        }
        if (isset($data['description'])) {
            $data['description'] = \App\Helpers\SanitizeHelper::description($data['description']);
        }
        
        // --- AQUI ESTÁ A MÁGICA ---
        // Qualquer edição joga o espaço de volta para análise
        $data['status'] = SpaceStatus::EmAnalise;
        $data['active'] = false; 
        // --------------------------
    
        // Atualiza imagem principal se fornecida com validação de MIME
        if ($request->hasFile('photo')) {
            $photo = $request->file('photo');
            // Validação adicional de MIME real
            $mimeValidator = new \App\Rules\ValidImageMime();
            if (!$mimeValidator->passes('photo', $photo)) {
                return response()->json([
                    'message' => 'O arquivo deve ser uma imagem válida (JPEG, PNG ou WebP).',
                ], 422);
            }
            if ($space->main_image) {
                Storage::disk('public')->delete($space->main_image);
            }
            $data['main_image'] = $photo->store('spaces', 'public');
        }
    
        $space->update($data);
        
        // Processa fotos adicionais (se houver)
        if ($request->hasFile('photos')) {
            $photos = $request->file('photos');
            // Garante que seja array mesmo se vier um único arquivo
            if (!is_array($photos)) {
                $photos = [$photos];
            }
            
            foreach ($photos as $photo) {
                if ($photo && $photo->isValid()) {
                    $path = $photo->store("spaces/{$space->id}", 'public');
                    $space->photos()->create(['path' => $path]);
                }
            }
        }
        
        // Recarrega relacionamentos
        $space->load('photos');
    
        return new SpaceResource($space);
    }

    public function destroy(Request $request, $id)
    {
        $space = Space::findOrFail($id);
        if ($request->user()->cannot('delete', $space)) return response()->json(['message' => 'Acesso negado'], 403);
        $space->delete();
        return response()->json(null, 204);
    }

    public function show($id)
    {
        // Detalhes públicos (deve seguir a mesma regra do index se for acesso público, mas se for direto por ID, geralmente deixamos passar se tiver link)
        return new SpaceResource(Space::with('photos')->findOrFail($id));
    }
}