<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class FilterClientRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, array<int, string>>
     */
    public function rules(): array
    {
        return [
            'cliente' => ['nullable', 'string', 'max:150'],
            'search' => ['nullable', 'string', 'max:150'],
            'residencia_id' => ['nullable', 'integer', 'exists:catalogo_valores,id'],
            'provincia_id' => ['nullable', 'integer'],
            'distrito_id' => ['nullable', 'integer'],
            'corregimiento_id' => ['nullable', 'integer'],
            'calle' => ['nullable', 'string', 'max:120'],
            'numero' => ['nullable', 'string', 'max:40'],
            'codigo_postal' => ['nullable', 'string', 'max:20'],
            'per_page' => ['nullable', 'integer', 'min:1'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
