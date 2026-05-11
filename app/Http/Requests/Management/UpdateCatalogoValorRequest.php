<?php

declare(strict_types=1);

namespace App\Http\Requests\Management;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCatalogoValorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'valor' => ['required', 'string', 'max:255'],
            'descripcion' => ['nullable', 'string'],
            'parent_id' => ['nullable', 'integer', 'exists:catalogo_valores,id'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'valor.required' => 'El valor es obligatorio.',
            'valor.max' => 'El valor no debe tener más de 255 caracteres.',
            'parent_id.exists' => 'El valor padre seleccionado no existe.',
            'sort_order.integer' => 'El orden debe ser un número entero.',
        ];
    }
}
