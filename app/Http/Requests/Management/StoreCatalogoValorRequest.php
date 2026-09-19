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
            'catalogo_id' => ['nullable', 'integer', 'exists:catalogos,id'],
            'codigo' => [
                'required',
                'string',
                'max:100',
                Rule::unique('catalogo_valores')->where(function ($query) {
                    $catalogo = $this->route('catalogo');
                    $catalogoId = $this->input('catalogo_id')
                        ?? (is_object($catalogo) ? $catalogo->id : $catalogo);

                    return $query->where('catalogo_id', $catalogoId)
                        ->where('tenant_id', $this->input('tenant_id'));
                }),
            ],
            'valor' => ['required', 'string', 'max:255'],
            'descripcion' => ['nullable', 'string'],
            'metadata' => ['nullable', 'array'],
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
            'codigo.max' => 'El código no debe tener más de 100 caracteres.',
            'valor.required' => 'El valor es obligatorio.',
            'valor.max' => 'El valor no debe tener más de 255 caracteres.',
            'parent_id.exists' => 'El valor padre seleccionado no existe.',
            'sort_order.integer' => 'El orden debe ser un número entero.',
            'tenant_id.exists' => 'El tenant seleccionado no existe.',
        ];
    }
}
