<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class WarehouseStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $tenantId = auth()->user()?->tenant_id;

        return [
            'name'             => ['required', 'string', 'max:255'],
            'code'             => [
                'required',
                'string',
                'max:50',
                Rule::unique('warehouses')->where(fn ($q) => $q->where('tenant_id', $tenantId)),
            ],
            'location_address' => ['nullable', 'string', 'max:500'],
            'location_coords'  => ['nullable', 'array'],
            'phone'            => ['nullable', 'string', 'max:50'],
            'is_active'        => ['boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required'             => 'El nombre es obligatorio.',
            'name.max'                  => 'El nombre no puede superar 255 caracteres.',
            'code.required'             => 'El código es obligatorio.',
            'code.unique'               => 'El código ya está en uso.',
            'code.max'                  => 'El código no puede superar 50 caracteres.',
            'location_address.max'      => 'La dirección no puede superar 500 caracteres.',
            'location_coords.array'     => 'Las coordenadas deben ser un objeto válido.',
            'phone.max'                 => 'El teléfono no puede superar 50 caracteres.',
            'is_active.boolean'         => 'El estado debe ser verdadero o falso.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'name'             => 'nombre',
            'code'             => 'código',
            'location_address' => 'dirección',
            'location_coords'  => 'coordenadas',
            'phone'            => 'teléfono',
            'is_active'        => 'activo',
        ];
    }
}
