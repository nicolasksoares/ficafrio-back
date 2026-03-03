<?php

namespace App\Services;

use Illuminate\Http\Request;
use App\Exceptions\InvalidWebhookSignatureException;
use Illuminate\Support\Facades\Log;

class WebhookValidationService
{
    /**
     * Valida assinatura do webhook
     * 
     * @param Request $request
     * @param string $signatureHeader Nome do header com a assinatura (ex: 'X-Signature')
     * @return bool
     * @throws InvalidWebhookSignatureException
     */
    public function validateSignature(Request $request, string $signatureHeader = 'X-Signature'): bool
    {
        $webhookSecret = config('payment.gateway_config.webhook_secret');
        
        // Se não há secret configurado, apenas loga (modo desenvolvimento)
        if (!$webhookSecret) {
            Log::warning('Webhook secret not configured, skipping signature validation');
            return true; // Em desenvolvimento, permite sem validação
        }

        $signature = $request->header($signatureHeader);
        
        if (!$signature) {
            Log::warning('Webhook signature header missing', [
                'headers' => $request->headers->all(),
            ]);
            throw new InvalidWebhookSignatureException('Assinatura não encontrada no header.');
        }

        // Obtém o payload raw (importante para validação)
        $payload = $request->getContent();
        
        // Calcula hash esperado (HMAC SHA256 é comum)
        $expectedSignature = hash_hmac('sha256', $payload, $webhookSecret);
        
        // Compara assinaturas de forma segura (timing-safe)
        if (!hash_equals($expectedSignature, $signature)) {
            Log::warning('Webhook signature validation failed', [
                'expected' => substr($expectedSignature, 0, 10) . '...',
                'received' => substr($signature, 0, 10) . '...',
            ]);
            throw new InvalidWebhookSignatureException('Assinatura inválida.');
        }

        return true;
    }

    /**
     * Valida timestamp do webhook (prevenção de replay attacks)
     * 
     * @param Request $request
     * @param int $maxAge Segundos máximos de idade permitida (padrão: 300 = 5 minutos)
     * @return bool
     */
    public function validateTimestamp(Request $request, int $maxAge = 300): bool
    {
        $timestamp = $request->header('X-Timestamp') ?? $request->input('timestamp');
        
        if (!$timestamp) {
            // Se não há timestamp, permite (alguns gateways não enviam)
            return true;
        }

        $age = time() - (int) $timestamp;
        
        if ($age > $maxAge || $age < 0) {
            Log::warning('Webhook timestamp validation failed', [
                'timestamp' => $timestamp,
                'age' => $age,
                'max_age' => $maxAge,
            ]);
            throw new InvalidWebhookSignatureException('Webhook expirado ou timestamp inválido.');
        }

        return true;
    }
}

