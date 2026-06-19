<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Models\Shipment;
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
            'package_type' => ['sometimes', 'string', 'max:100'],
            'weight_lb' => ['sometimes', 'numeric', 'min:0'],
            'content_description' => ['sometimes', 'nullable', 'string'],
            'weight_kg' => ['sometimes', 'nullable', 'numeric', 'min:0'],
            'dimensions' => ['sometimes', 'string', 'max:255'],
            'destination_coords' => ['sometimes', 'string', 'max:255'],
            'sender_id' => ['sometimes', 'nullable', 'exists:clients,id'],
            'warehouse_id' => ['sometimes', 'nullable', 'exists:warehouses,id'],
            'status' => ['sometimes', 'string', 'in:'.implode(',', Shipment::STATUSES)],
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
