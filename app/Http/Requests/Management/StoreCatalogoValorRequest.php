<?php

declare(strict_types=1);

namespace App\Http\Requests\Management;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCatalogoValorRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'catalogo_id' => ['required', 'integer', 'exists:catalogos,id'],
            'codigo' => ['required', 'string', 'max:3'],
            'valor' => ['required', 'string', 'max:255'],
            'descripcion' => ['nullable', 'string'],
            'parent_id' => ['nullable', 'integer', 'exists:catalogo_valores,id'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['boolean'],
            'is_global' => ['boolean'],
            'tenant_id' => ['nullable', 'uuid', Rule::exists('tenants', 'id')],
        ];
    }

    public function messages(): array
    {
        return [
            'catalogo_id.required' => 'El catálogo es obligatorio.',
            'catalogo_id.exists' => 'El catálogo seleccionado no existe.',
            'codigo.required' => 'El código es obligatorio.',
            'codigo.max' => 'El código no debe tener más de 3 caracteres.',
            'valor.required' => 'El valor es obligatorio.',
            'valor.max' => 'El valor no debe tener más de 255 caracteres.',
            'parent_id.exists' => 'El valor padre seleccionado no existe.',
            'sort_order.integer' => 'El orden debe ser un número entero.',
            'tenant_id.exists' => 'El tenant seleccionado no existe.',
        ];
    }
}
