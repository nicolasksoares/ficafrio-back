<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Http\UploadedFile;

class ValidImageMime implements Rule
{
    /**
     * MIME types permitidos para imagens
     */
    private array $allowedMimes = [
        'image/jpeg',
        'image/jpg',
        'image/png',
        'image/webp',
    ];

    /**
     * Determine if the validation rule passes.
     */
    public function passes($attribute, $value): bool
    {
        if (!$value instanceof UploadedFile) {
            return false;
        }

        // Valida MIME type real do arquivo (não confia na extensão)
        $mimeType = $value->getMimeType();
        
        // Valida também usando finfo (mais seguro) se disponível
        $realMimeType = null;
        if (function_exists('mime_content_type') && $value->getRealPath()) {
            $realMimeType = mime_content_type($value->getRealPath());
        }
        
        // Valida MIME type do Laravel
        $isValid = in_array($mimeType, $this->allowedMimes);
        
        // Se finfo estiver disponível, valida também
        if ($realMimeType !== null) {
            $isValid = $isValid && in_array($realMimeType, $this->allowedMimes);
        }
        
        return $isValid;
    }

    /**
     * Get the validation error message.
     */
    public function message(): string
    {
        return 'O arquivo deve ser uma imagem válida (JPEG, PNG ou WebP).';
    }
}

