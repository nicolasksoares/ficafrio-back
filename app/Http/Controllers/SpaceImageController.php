<?php

namespace App\Http\Controllers;

use App\Helpers\ImageUrlHelper;
use App\Http\Requests\StoreSpacePhotoRequest;
use App\Models\Space;
use App\Models\SpacePhoto;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class SpaceImageController extends Controller
{
    /**
     * Upload de foto para espaço
     * 
     * Melhorias:
     * - Validação robusta via FormRequest
     * - Nome de arquivo único
     * - Suporte para múltiplos formatos
     * - Limpeza de cache automática
     */
    public function store(StoreSpacePhotoRequest $request, $id)
    {
        $space = Space::findOrFail($id);

        if ($request->user()->cannot('update', $space)) {
            return response()->json(['message' => 'Acesso negado.'], 403);
        }

        try {
            $file = $request->file('photo');
            
            // Validação adicional de MIME real (além do FormRequest)
            $mimeValidator = new \App\Rules\ValidImageMime();
            if (!$mimeValidator->passes('photo', $file)) {
                return response()->json([
                    'message' => 'O arquivo deve ser uma imagem válida (JPEG, PNG ou WebP).',
                ], 422);
            }
            
            // Gera nome único para evitar conflitos
            $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs("spaces/{$space->id}", $filename, 'public');

            /** @var \App\Models\SpacePhoto $photo */
            $photo = $space->photos()->create([
                'path' => $path,
            ]);

            // Limpa cache da URL
            ImageUrlHelper::clearCache($path);

            return response()->json([
                'message' => 'Foto enviada com sucesso!',
                'url' => ImageUrlHelper::url($path), 
                'id' => $photo->id,
            ], 201);
        } catch (\Exception $e) {
            \Log::error('Erro ao fazer upload de foto', [
                'space_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Erro ao fazer upload da imagem. Tente novamente.',
            ], 500);
        }
    }

    /**
     * Deleta foto de espaço
     * 
     * Melhorias:
     * - Limpeza de cache
     * - Tratamento de erros
     */
    public function destroy(Request $request, $id)
    {
        $photo = SpacePhoto::findOrFail($id);
        $space = $photo->space;

        if ($request->user()->cannot('update', $space)) {
            return response()->json(['message' => 'Acesso negado.'], 403);
        }

        try {
            $path = $photo->path;

            // Deleta arquivo físico
            if (Storage::disk('public')->exists($path)) {
                Storage::disk('public')->delete($path);
            }

            // Deleta registro
            $photo->delete();

            // Limpa cache
            ImageUrlHelper::clearCache($path);

            return response()->json(null, 204);
        } catch (\Exception $e) {
            \Log::error('Erro ao deletar foto', [
                'photo_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Erro ao deletar foto.',
            ], 500);
        }
    }
}