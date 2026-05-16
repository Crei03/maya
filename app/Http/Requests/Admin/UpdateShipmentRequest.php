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

    public function rules(): array
    {
        return [
            'recipient_name'      => ['sometimes', 'string', 'max:255'],
            'destination_address'  => ['sometimes', 'string', 'max:500'],
            'package_type'         => ['sometimes', 'string', 'max:100'],
            'weight_kg'            => ['sometimes', 'numeric', 'min:0'],
            'content_description'  => ['sometimes', 'nullable', 'string'],
            'weight_lb'            => ['sometimes', 'numeric', 'min:0'],
            'dimensions'           => ['sometimes', 'string', 'max:255'],
            'origin_address'       => ['sometimes', 'string', 'max:500'],
            'destination_coords'   => ['sometimes', 'string', 'max:255'],
            'recipient_phone'      => ['sometimes', 'string', 'max:50'],
            'sender_id'            => ['sometimes', 'nullable', 'exists:clients,id'],
            'warehouse_id'         => ['sometimes', 'nullable', 'exists:warehouses,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'weight_kg.numeric'      => 'El peso debe ser un valor numérico.',
            'weight_kg.min'          => 'El peso no puede ser negativo.',
            'weight_lb.numeric'      => 'El peso en libras debe ser un valor numérico.',
            'weight_lb.min'          => 'El peso en libras no puede ser negativo.',
            'sender_id.exists'       => 'El remitente seleccionado no existe.',
            'warehouse_id.exists'    => 'La bodega seleccionada no existe.',
        ];
    }

    public function attributes(): array
    {
        return [
            'recipient_name'      => 'nombre del destinatario',
            'destination_address'  => 'dirección de destino',
            'package_type'         => 'tipo de paquete',
            'weight_kg'            => 'peso en kilogramos',
            'content_description'  => 'descripción del contenido',
            'weight_lb'            => 'peso en libras',
            'dimensions'           => 'dimensiones',
            'origin_address'       => 'dirección de origen',
            'destination_coords'   => 'coordenadas de destino',
            'recipient_phone'      => 'teléfono del destinatario',
            'sender_id'            => 'remitente',
            'warehouse_id'         => 'bodega',
        ];
    }
}
