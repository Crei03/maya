<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateShipmentRequest extends FormRequest
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
        return [
            'destination_address' => ['sometimes', 'string', 'max:500'],
            'package_type' => ['sometimes', 'nullable', 'string', 'max:100'],
            'package_type_id' => ['sometimes', 'nullable', 'exists:catalogo_valores,id'],
            'weight_lb' => ['sometimes', 'numeric', 'min:0'],
            'content_description' => ['sometimes', 'nullable', 'string'],
            'weight_kg' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'dimensions' => ['sometimes', 'nullable', 'string', 'max:255'],
            'destination_coords' => ['sometimes', 'nullable'],
            'sender_id' => ['sometimes', 'nullable', 'exists:clients,id'],
            'recipient_name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'recipient_phone' => ['sometimes', 'nullable', 'string', 'max:50'],
            'warehouse_id' => ['sometimes', 'nullable', 'exists:warehouses,id'],
            'reference_type' => ['sometimes', 'nullable', 'string', 'max:50'],
            'reference_type_id' => ['sometimes', 'nullable', 'exists:catalogo_valores,id'],
            'reference_number' => ['sometimes', 'nullable', 'string', 'max:100'],
            'lpn_code' => ['sometimes', 'nullable', 'string', 'max:100'],
            'pieces_count' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'total_cost' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'status' => ['sometimes', 'nullable'],
            'status_id' => ['sometimes', 'nullable', 'exists:catalogo_valores,id'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'weight_lb.numeric' => 'El peso en libras debe ser un valor numérico.',
            'weight_lb.min' => 'El peso en libras no puede ser negativo.',
            'weight_kg.numeric' => 'El peso debe ser un valor numérico.',
            'weight_kg.min' => 'El peso no puede ser negativo.',
            'sender_id.exists' => 'El remitente seleccionado no existe.',
            'warehouse_id.exists' => 'La bodega seleccionada no existe.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'destination_address' => 'dirección de destino',
            'package_type' => 'tipo de paquete',
            'weight_lb' => 'peso en libras',
            'weight_kg' => 'peso en kilogramos',
            'content_description' => 'descripción del contenido',
            'dimensions' => 'dimensiones',
            'destination_coords' => 'coordenadas de destino',
            'sender_id' => 'remitente',
            'warehouse_id' => 'bodega',
            'status' => 'estado',
        ];
    }
}
