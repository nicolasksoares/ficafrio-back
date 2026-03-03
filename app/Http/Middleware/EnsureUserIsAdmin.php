<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Enums\UserType; // Importe seu Enum

class EnsureUserIsAdmin
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // 1. Verifica se o usuário está logado (redundância de segurança)
        if (! $request->user()) {
            return response()->json(['message' => 'Não autenticado.'], 401);
        }

        // 2. A MÁGICA: Verifica se o tipo é Admin
        if ($request->user()->type !== UserType::Admin) {
            // Se for Parceiro ou Cliente tentando entrar aqui, BLOQUEIA.
            return response()->json(['message' => 'Acesso não autorizado. Área restrita.'], 403);
        }

        return $next($request);
    }
}