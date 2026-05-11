<?php

declare(strict_types=1);

namespace App\Http\Requests\Management;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCatalogoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isManagement() ?? false;
    }

    public function rules(): array
    {
        $catalogoId = $this->route('catalogo')?->id ?? $this->route('catalogo');

        return [
            'nombre' => ['required', 'string', 'max:255', Rule::unique('catalogos', 'nombre')->ignore($catalogoId)],
            'description' => ['nullable', 'string', 'max:255'],
            'is_active' => ['boolean'],
            'sort_order' => ['integer', 'min:0'],
            'is_global' => ['boolean'],
            'tenant_id' => [
                'nullable',
                'uuid',
                Rule::exists('tenants', 'id'),
                Rule::requiredIf(function () {
                    return $this->input('is_global') === false || $this->input('is_global') === '0';
                }),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'nombre.required' => 'El campo nombre es obligatorio.',
            'nombre.unique' => 'Ya existe un catálogo con este nombre.',
            'tenant_id.required_if' => 'El tenant es obligatorio cuando el catálogo no es global.',
            'tenant_id.exists' => 'El tenant seleccionado no existe.',
        ];
    }

    protected function prepareForValidation(): void
    {
        if ($this->input('is_global') === true || $this->input('is_global') === '1') {
            $this->merge(['tenant_id' => null]);
        }
    }
}
