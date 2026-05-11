<?php

declare(strict_types=1);

namespace App\Http\Requests\Management;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCatalogoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isManagement() ?? false;
    }

    public function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:255', 'unique:catalogos,nombre'],
            'slug' => ['required', 'string', 'max:100', 'unique:catalogos,slug', 'regex:/^[a-z0-9-]+$/'],
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
            'slug.required' => 'El campo slug es obligatorio.',
            'slug.unique' => 'Ya existe un catálogo con este slug.',
            'slug.regex' => 'El slug solo puede contener letras minúsculas, números y guiones.',
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
