<?php

namespace App\Helpers;

use Illuminate\Support\Str;

class SanitizeHelper
{
    /**
     * Remove tags HTML e limita o tamanho do texto
     */
    public static function text(string $text, int $maxLength = 255): string
    {
        // Remove tags HTML
        $sanitized = strip_tags($text);
        
        // Remove caracteres de controle
        $sanitized = preg_replace('/[\x00-\x1F\x7F]/u', '', $sanitized);
        
        // Limita o tamanho
        return Str::limit(trim($sanitized), $maxLength);
    }

    /**
     * Sanitiza texto longo (descrições, mensagens)
     */
    public static function longText(string $text, int $maxLength = 5000): string
    {
        // Remove tags HTML
        $sanitized = strip_tags($text);
        
        // Remove caracteres de controle
        $sanitized = preg_replace('/[\x00-\x1F\x7F]/u', '', $sanitized);
        
        // Limita o tamanho
        return Str::limit(trim($sanitized), $maxLength);
    }

    /**
     * Sanitiza título
     */
    public static function title(string $title): string
    {
        return self::text($title, 255);
    }

    /**
     * Sanitiza descrição
     */
    public static function description(?string $description): ?string
    {
        if (empty($description)) {
            return null;
        }
        
        return self::longText($description, 5000);
    }
}

