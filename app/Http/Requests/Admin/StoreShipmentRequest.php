<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreShipmentRequest extends FormRequest
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
            'destination_address' => ['required', 'string', 'max:500'],
            'package_type' => ['required', 'string', 'max:100'],
            'weight_lb' => ['required', 'numeric', 'min:0'],
            'content_description' => ['nullable', 'string'],
            'weight_kg' => ['nullable', 'numeric', 'min:0'],
            'dimensions' => ['nullable', 'string', 'max:255'],
            'destination_coords' => ['nullable', 'string', 'max:255'],
            'sender_id' => ['required', 'exists:clients,id'],
            'warehouse_id' => ['required', 'exists:warehouses,id'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'destination_address.required' => 'La dirección de destino es obligatoria.',
            'package_type.required' => 'El tipo de paquete es obligatorio.',
            'weight_lb.required' => 'El peso en libras es obligatorio.',
            'weight_lb.numeric' => 'El peso en libras debe ser un valor numérico.',
            'weight_lb.min' => 'El peso en libras no puede ser negativo.',
            'weight_kg.numeric' => 'El peso debe ser un valor numérico.',
            'weight_kg.min' => 'El peso no puede ser negativo.',
            'sender_id.required' => 'El remitente es obligatorio.',
            'sender_id.exists' => 'El remitente seleccionado no existe.',
            'warehouse_id.required' => 'La bodega es obligatoria.',
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
        ];
    }
}
