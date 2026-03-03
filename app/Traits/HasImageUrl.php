<?php

namespace App\Traits;

use App\Helpers\ImageUrlHelper;

/**
 * Trait para modelos que possuem imagens
 * Centraliza lógica de geração de URLs
 */
trait HasImageUrl
{
    /**
     * Gera URL para uma imagem
     * 
     * @param string|null $path Caminho da imagem
     * @param string $disk Disco de storage
     * @return string|null
     */
    protected function getImageUrl(?string $path, string $disk = 'public'): ?string
    {
        return ImageUrlHelper::url($path, $disk);
    }

    /**
     * Limpa cache de URL quando imagem é atualizada
     * 
     * @param string|null $oldPath
     * @param string $disk
     * @return void
     */
    protected function clearImageUrlCache(?string $oldPath, string $disk = 'public'): void
    {
        if ($oldPath) {
            ImageUrlHelper::clearCache($oldPath, $disk);
        }
    }
}

