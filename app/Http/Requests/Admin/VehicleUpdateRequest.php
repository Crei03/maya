<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class VehicleUpdateRequest extends FormRequest
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
        $tenantId  = auth()->user()?->tenant_id;
        $vehicleId = $this->route('vehicle');

        return [
            'license_plate'   => [
                'required',
                'string',
                'max:20',
                Rule::unique('vehicles')
                    ->where(fn ($q) => $q->where('tenant_id', $tenantId))
                    ->ignore($vehicleId),
            ],
            'type'            => ['required', Rule::in(['internal', 'external'])],
            'brand'           => ['required', 'string', 'max:100'],
            'model'           => ['required', 'string', 'max:100'],
            'year'            => ['required', 'integer', 'min:1900', 'max:2100'],
            'capacity_kg'     => ['nullable', 'numeric', 'min:0'],
            'capacity_volume' => ['nullable', 'string', 'max:100'],
            'color'           => ['nullable', 'string', 'max:50'],
            'is_active'       => ['boolean'],
            'notes'           => ['nullable', 'string'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'license_plate.required' => 'La placa es obligatoria.',
            'license_plate.unique'   => 'Esta placa ya está registrada en el sistema.',
            'license_plate.max'      => 'La placa no puede superar 20 caracteres.',
            'type.required'          => 'El tipo de vehículo es obligatorio.',
            'type.in'                => 'El tipo debe ser interno o externo.',
            'brand.required'         => 'La marca es obligatoria.',
            'model.required'         => 'El modelo es obligatorio.',
            'year.required'          => 'El año es obligatorio.',
            'year.integer'           => 'El año debe ser un número entero.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'license_plate'   => 'placa',
            'type'            => 'tipo',
            'brand'           => 'marca',
            'model'           => 'modelo',
            'year'            => 'año',
            'capacity_kg'     => 'capacidad (kg)',
            'capacity_volume' => 'capacidad volumétrica',
            'color'           => 'color',
            'is_active'       => 'activo',
            'notes'           => 'notas',
        ];
    }
}
