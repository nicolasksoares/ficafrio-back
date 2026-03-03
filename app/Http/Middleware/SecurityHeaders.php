<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware para adicionar headers de segurança HTTP
 * 
 * Implementa:
 * - Content Security Policy (CSP)
 * - HTTP Strict Transport Security (HSTS)
 * - X-Frame-Options
 * - X-Content-Type-Options
 * - Referrer-Policy
 * - Permissions-Policy
 */
class SecurityHeaders
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Apenas adiciona headers em produção ou se explicitamente habilitado
        if (config('app.env') === 'production' || config('app.security_headers_enabled', false)) {
            $this->addSecurityHeaders($response, $request);
        }

        return $response;
    }

    /**
     * Adiciona headers de segurança à resposta
     */
    private function addSecurityHeaders(Response $response, Request $request): void
    {
        // Content Security Policy (CSP)
        $csp = $this->buildCSP($request);
        if ($csp) {
            $response->headers->set('Content-Security-Policy', $csp);
        }

        // HTTP Strict Transport Security (HSTS)
        if ($request->secure()) {
            $maxAge = config('app.hsts_max_age', 31536000); // 1 ano
            $response->headers->set('Strict-Transport-Security', "max-age={$maxAge}; includeSubDomains; preload");
        }

        // X-Frame-Options (previne clickjacking)
        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');

        // X-Content-Type-Options (previne MIME sniffing)
        $response->headers->set('X-Content-Type-Options', 'nosniff');

        // Referrer-Policy
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');

        // Permissions-Policy (antigo Feature-Policy)
        $permissionsPolicy = $this->buildPermissionsPolicy();
        $response->headers->set('Permissions-Policy', $permissionsPolicy);

        // X-XSS-Protection (legado, mas ainda útil)
        $response->headers->set('X-XSS-Protection', '1; mode=block');
    }

    /**
     * Constrói Content Security Policy
     */
    private function buildCSP(Request $request): ?string
    {
        $cspConfig = config('app.csp', []);

        // Se CSP está desabilitado, retorna null
        if (!($cspConfig['enabled'] ?? true)) {
            return null;
        }

        $directives = [];

        // default-src
        $defaultSrc = $cspConfig['default-src'] ?? ["'self'"];
        $directives[] = "default-src " . implode(' ', $defaultSrc);

        // script-src
        $scriptSrc = $cspConfig['script-src'] ?? ["'self'", "'unsafe-inline'", "'unsafe-eval'"];
        // Adiciona nonce para Vite HMR em desenvolvimento
        if (config('app.env') === 'local') {
            $scriptSrc[] = "'unsafe-inline'";
            $scriptSrc[] = "'unsafe-eval'";
        }
        $directives[] = "script-src " . implode(' ', array_unique($scriptSrc));

        // style-src
        $styleSrc = $cspConfig['style-src'] ?? ["'self'", "'unsafe-inline'"];
        $directives[] = "style-src " . implode(' ', $styleSrc);

        // img-src
        $imgSrc = $cspConfig['img-src'] ?? ["'self'", 'data:', 'https:'];
        $directives[] = "img-src " . implode(' ', $imgSrc);

        // font-src
        $fontSrc = $cspConfig['font-src'] ?? ["'self'", 'data:', 'https:'];
        $directives[] = "font-src " . implode(' ', $fontSrc);

        // connect-src (para API calls, WebSockets, etc)
        $connectSrc = $cspConfig['connect-src'] ?? ["'self'"];
        if (config('app.env') === 'local') {
            $connectSrc[] = 'ws://localhost:*';
            $connectSrc[] = 'http://localhost:*';
        }
        $directives[] = "connect-src " . implode(' ', array_unique($connectSrc));

        // frame-src
        $frameSrc = $cspConfig['frame-src'] ?? ["'self'"];
        $directives[] = "frame-src " . implode(' ', $frameSrc);

        // object-src
        $directives[] = "object-src 'none'";

        // base-uri
        $directives[] = "base-uri 'self'";

        // form-action
        $directives[] = "form-action 'self'";

        // upgrade-insecure-requests (em produção com HTTPS)
        if ($request->secure()) {
            $directives[] = "upgrade-insecure-requests";
        }

        return implode('; ', $directives);
    }

    /**
     * Constrói Permissions-Policy
     */
    private function buildPermissionsPolicy(): string
    {
        $policies = [
            'geolocation' => "'self'",
            'microphone' => "'none'",
            'camera' => "'none'",
            'payment' => "'self'",
            'usb' => "'none'",
            'magnetometer' => "'none'",
            'gyroscope' => "'none'",
            'speaker' => "'self'",
            'vibrate' => "'none'",
            'fullscreen' => "'self'",
            'sync-xhr' => "'self'",
        ];

        $policyParts = [];
        foreach ($policies as $feature => $allowlist) {
            $policyParts[] = "{$feature}=({$allowlist})";
        }

        return implode(', ', $policyParts);
    }
}

