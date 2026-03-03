<?php

namespace App\Http\Requests;

use App\Rules\ValidImageMime;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSpacePhotoRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Autorização feita no controller
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        $maxSize = config('image.max_size', 5 * 1024 * 1024);
        $minWidth = config('image.min_dimensions.width', 200);
        $minHeight = config('image.min_dimensions.height', 200);
        $allowedFormats = config('image.allowed_formats', ['jpg', 'jpeg', 'png', 'webp']);

        return [
            'photo' => [
                'required',
                'file',
                'mimes:' . implode(',', $allowedFormats),
                'max:' . ($maxSize / 1024), // Converte para KB
                'dimensions:min_width=' . $minWidth . ',min_height=' . $minHeight,
                new ValidImageMime(), // Validação de MIME real
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        $maxSizeMB = round(config('image.max_size', 5 * 1024 * 1024) / 1024 / 1024, 1);
        $allowedFormats = implode(', ', array_map('strtoupper', config('image.allowed_formats', [])));

        return [
            'photo.required' => 'É necessário selecionar uma imagem.',
            'photo.file' => 'O arquivo enviado não é válido.',
            'photo.mimes' => "Apenas imagens {$allowedFormats} são permitidas.",
            'photo.max' => "A imagem deve ter no máximo {$maxSizeMB}MB.",
            'photo.dimensions' => 'A imagem deve ter no mínimo 200x200 pixels.',
        ];
    }
}

