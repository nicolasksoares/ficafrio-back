<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class LogRequests
{
    public function handle(Request $request, Closure $next): Response
    {
        Log::info('➡️ [REQ] ' . $request->method() . ' ' . $request->fullUrl(), [
            'payload' => $request->all(),
            'ip' => $request->ip(),
        ]);

        $response = $next($request);

        Log::info('⬅️ [RES] Status: ' . $response->getStatusCode(), [
            'content' => $this->shouldLogContent($response) ? substr($response->getContent(), 0, 1000) : 'Content omitted',
        ]);

        return $response;
    }

    private function shouldLogContent($response)
    {
        // Evita logar arquivos binários ou respostas gigantes
        return $response->headers->get('Content-Type') === 'application/json';
    }
}