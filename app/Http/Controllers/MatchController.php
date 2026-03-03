<?php

namespace App\Http\Controllers;

use App\Http\Resources\SpaceResource;
use App\Models\StorageRequest;
use App\Services\MatchingService; // <--- Importante
use Illuminate\Http\Request;

class MatchController extends Controller
{
    protected $matchingService;

    public function __construct(MatchingService $matchingService)
    {
        $this->matchingService = $matchingService;
    }

    public function index(Request $request, $id)
    {
        $storageRequest = StorageRequest::findOrFail($id);

        if ($request->user()->cannot('delete', $storageRequest)) {
            return response()->json(['message' => 'Acesso proibido'], 403);
        }

        $matches = $this->matchingService->findMatches($storageRequest);
        $matches->load('company');

        // Retorna os espaços encontrados já formatados (sem vazar dados internos)
        return SpaceResource::collection($matches);
    }
}
