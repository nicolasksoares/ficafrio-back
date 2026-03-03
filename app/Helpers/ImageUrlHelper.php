<?php

namespace App\Helpers;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Cache;

/**
 * Helper centralizado para gerenciar URLs de imagens
 * 
 * Benefícios:
 * - Suporte para CDN/S3
 * - Cache de URLs
 * - Consistência em toda aplicação
 * - Fácil migração para storage em nuvem
 */
class ImageUrlHelper
{
    /**
     * Gera URL absoluta para uma imagem no storage
     * 
     * @param string|null $path Caminho relativo da imagem
     * @param string $disk Disco de storage (padrão: 'public')
     * @param bool $useCache Usar cache (padrão: true)
     * @return string|null URL absoluta ou null se path vazio
     */
    public static function url(?string $path, string $disk = null, bool $useCache = true): ?string
    {
        if (empty($path)) {
            return null;
        }

        // Usa disco padrão da configuração se não especificado
        $disk = $disk ?? config('image.default_disk', 'public');
        
        // Verifica se há CDN configurado
        $cdnUrl = config('image.cdn_url');
        if ($cdnUrl) {
            $cdnPath = rtrim($cdnUrl, '/') . '/' . ltrim($path, '/');
            return $cdnPath;
        }

        // Cache key baseada no path e disk
        $shouldCache = $useCache && config('image.cache_urls', true);
        $cacheKey = $shouldCache ? "image_url:{$disk}:{$path}" : null;

        if ($shouldCache && $cacheKey && Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        try {
            $storageUrl = Storage::disk($disk)->url($path);
            
            // Se já é URL absoluta (ex: S3/CDN), retorna direto
            if (str_starts_with($storageUrl, 'http://') || str_starts_with($storageUrl, 'https://')) {
                $url = $storageUrl;
            } else {
                // Converte para URL absoluta usando APP_URL
                $url = url($storageUrl);
                
                // CORREÇÃO: Garante que localhost tenha porta 8000 se não tiver
                // Isso resolve o problema de URLs como http://localhost/storage/...
                if (str_contains($url, 'localhost') && !str_contains($url, 'localhost:')) {
                    $url = str_replace('http://localhost', 'http://localhost:8000', $url);
                }
            }

            // Cache com TTL configurável
            if ($shouldCache && $cacheKey) {
                $ttl = config('image.cache_ttl', 86400);
                Cache::put($cacheKey, $url, now()->addSeconds($ttl));
            }

            return $url;
        } catch (\Exception $e) {
            // Log erro mas não quebra a aplicação
            \Log::warning("Erro ao gerar URL de imagem: {$path}", [
                'error' => $e->getMessage(),
                'disk' => $disk
            ]);
            
            return null;
        }
    }

    /**
     * Gera múltiplas URLs de uma vez (otimizado)
     * 
     * @param array $paths Array de caminhos
     * @param string $disk Disco de storage
     * @return array Array de URLs
     */
    public static function urls(array $paths, string $disk = 'public'): array
    {
        return array_map(function ($path) use ($disk) {
            return self::url($path, $disk);
        }, array_filter($paths));
    }

    /**
     * Verifica se uma URL é de CDN/externa
     * 
     * @param string|null $url
     * @return bool
     */
    public static function isExternal(?string $url): bool
    {
        if (empty($url)) {
            return false;
        }

        $appUrl = config('app.url');
        return !str_starts_with($url, $appUrl);
    }

    /**
     * Limpa cache de URLs de imagens
     * Útil após upload/deleção de imagens
     * 
     * @param string|null $path Caminho específico ou null para limpar tudo
     * @param string $disk Disco de storage
     * @return void
     */
    public static function clearCache(?string $path = null, string $disk = 'public'): void
    {
        if ($path) {
            Cache::forget("image_url:{$disk}:{$path}");
        } else {
            // Limpa todo cache de imagens (use com cuidado em produção)
            Cache::flush();
        }
    }

    /**
     * Gera URL com parâmetros de otimização (para CDN)
     * Ex: ?w=800&h=600&q=80
     * 
     * @param string|null $path
     * @param array $params Parâmetros de otimização
     * @param string $disk
     * @return string|null
     */
    public static function optimizedUrl(?string $path, array $params = [], string $disk = 'public'): ?string
    {
        $url = self::url($path, $disk);
        
        if (!$url || empty($params)) {
            return $url;
        }

        // Adiciona parâmetros de query se suportado pelo CDN
        $separator = str_contains($url, '?') ? '&' : '?';
        return $url . $separator . http_build_query($params);
    }
}

