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
            'package_type' => ['required_without:package_type_id', 'nullable', 'string', 'max:100'],
            'package_type_id' => ['nullable', 'exists:catalogo_valores,id'],
            'weight_lb' => ['required', 'numeric', 'min:0'],
            'content_description' => ['nullable', 'string'],
            'weight_kg' => ['nullable', 'numeric', 'min:0'],
            'dimensions' => ['nullable', 'string', 'max:255'],
            'destination_coords' => ['nullable'],
            'sender_id' => ['required_without:recipient_name', 'nullable', 'exists:clients,id'],
            'recipient_name' => ['required_without:sender_id', 'nullable', 'string', 'max:255'],
            'recipient_phone' => ['nullable', 'string', 'max:50'],
            'warehouse_id' => ['required', 'exists:warehouses,id'],
            'reference_type' => ['nullable', 'string', 'max:50'],
            'reference_type_id' => ['nullable', 'exists:catalogo_valores,id'],
            'status_id' => ['nullable', 'exists:catalogo_valores,id'],
            'reference_number' => ['nullable', 'string', 'max:100'],
            'lpn_code' => ['nullable', 'string', 'max:100'],
            'pieces_count' => ['nullable', 'integer', 'min:1'],
            'total_cost' => ['nullable', 'numeric', 'min:0'],
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
            'sender_id.required_without' => 'El remitente o cliente destinatario es obligatorio.',
            'sender_id.exists' => 'El remitente seleccionado no existe.',
            'recipient_name.required_without' => 'El nombre del destinatario es obligatorio si no selecciona un cliente.',
            'warehouse_id.required' => 'La bodega es obligatoria.',
            'warehouse_id.exists' => 'La bodega seleccionada no existe.',
            'pieces_count.min' => 'La cantidad de bultos debe ser al menos 1.',
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
            'recipient_name' => 'destinatario',
            'recipient_phone' => 'teléfono del destinatario',
            'warehouse_id' => 'bodega',
            'reference_type' => 'tipo de documento',
            'reference_number' => 'número de referencia',
            'lpn_code' => 'código LPN',
            'pieces_count' => 'cantidad de bultos',
            'total_cost' => 'costo total',
        ];
    }
}
