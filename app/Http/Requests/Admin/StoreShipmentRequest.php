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

    public function rules(): array
    {
        return [
            'recipient_name'      => ['required', 'string', 'max:255'],
            'destination_address'  => ['required', 'string', 'max:500'],
            'package_type'         => ['required', 'string', 'max:100'],
            'weight_kg'            => ['required', 'numeric', 'min:0'],
            'content_description'  => ['nullable', 'string'],
            'weight_lb'            => ['nullable', 'numeric', 'min:0'],
            'dimensions'           => ['nullable', 'string', 'max:255'],
            'origin_address'       => ['nullable', 'string', 'max:500'],
            'destination_coords'   => ['nullable', 'string', 'max:255'],
            'recipient_phone'      => ['nullable', 'string', 'max:50'],
            'sender_id'            => ['nullable', 'exists:clients,id'],
            'warehouse_id'         => ['nullable', 'exists:warehouses,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'recipient_name.required'     => 'El nombre del destinatario es obligatorio.',
            'destination_address.required' => 'La dirección de destino es obligatoria.',
            'package_type.required'        => 'El tipo de paquete es obligatorio.',
            'weight_kg.required'           => 'El peso en kilogramos es obligatorio.',
            'weight_kg.numeric'            => 'El peso debe ser un valor numérico.',
            'weight_kg.min'                => 'El peso no puede ser negativo.',
            'weight_lb.numeric'            => 'El peso en libras debe ser un valor numérico.',
            'weight_lb.min'                => 'El peso en libras no puede ser negativo.',
            'sender_id.exists'             => 'El remitente seleccionado no existe.',
            'warehouse_id.exists'          => 'La bodega seleccionada no existe.',
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
