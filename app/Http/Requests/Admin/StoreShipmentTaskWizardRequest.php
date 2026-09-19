<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Models\ShipmentTaskItem;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreShipmentTaskWizardRequest extends FormRequest
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
            'title' => ['nullable', 'string', 'max:100'],
            'driver_id' => ['required', 'integer', 'exists:users,id'],
            'vehicle_id' => ['nullable', 'string', 'exists:vehicles,id'],
            'origin_warehouse_id' => ['required', 'string', 'exists:warehouses,id'],
            'start_date' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.priority' => ['nullable', 'string'],
            'items.*.priority_id' => ['nullable', 'exists:catalogo_valores,id'],
            'items.*.stop_order' => ['required', 'integer', 'min:1'],
            'items.*.shipment_id' => ['nullable', 'string', 'exists:shipments,id'],
            'items.*.new_shipment' => ['nullable', 'array'],
            'items.*.new_shipment.sender_id' => ['nullable', 'string', 'exists:clients,id'],
            'items.*.new_shipment.recipient_name' => ['nullable', 'string', 'max:255'],
            'items.*.new_shipment.recipient_phone' => ['nullable', 'string', 'max:50'],
            'items.*.new_shipment.destination_address' => ['required_with:items.*.new_shipment', 'string', 'max:500'],
            'items.*.new_shipment.package_type' => ['nullable', 'string', 'max:100'],
            'items.*.new_shipment.package_type_id' => ['nullable', 'exists:catalogo_valores,id'],
            'items.*.new_shipment.weight_lb' => ['required_with:items.*.new_shipment', 'numeric', 'min:0'],
            'items.*.new_shipment.weight_kg' => ['nullable', 'numeric', 'min:0'],
            'items.*.new_shipment.reference_type' => ['nullable', 'string', 'max:50'],
            'items.*.new_shipment.reference_type_id' => ['nullable', 'exists:catalogo_valores,id'],
            'items.*.new_shipment.reference_number' => ['nullable', 'string', 'max:100'],
            'items.*.new_shipment.lpn_code' => ['nullable', 'string', 'max:100'],
            'items.*.new_shipment.pieces_count' => ['nullable', 'integer', 'min:1'],
            'items.*.new_shipment.content_description' => ['nullable', 'string', 'max:500'],
            'items.*.new_shipment.dimensions' => ['nullable', 'string', 'max:255'],
            'items.*.new_shipment.destination_coords' => ['nullable'],
        ];
    }

    public function messages(): array
    {
        return [
            'driver_id.required' => 'El conductor es obligatorio.',
            'driver_id.exists' => 'El conductor seleccionado no existe.',
            'origin_warehouse_id.required' => 'La bodega de salida es obligatoria.',
            'origin_warehouse_id.exists' => 'La bodega seleccionada no existe.',
            'start_date.required' => 'La fecha de salida es obligatoria.',
            'items.required' => 'Debe agregar al menos una parada al plan de entrega.',
            'items.min' => 'Debe agregar al menos una parada al plan de entrega.',
            'items.*.priority.in' => 'La prioridad debe ser alta, media o baja.',
            'items.*.stop_order.required' => 'El orden de parada es obligatorio.',
            'items.*.new_shipment.sender_id.required_with' => 'El remitente del paquete es obligatorio.',
            'items.*.new_shipment.destination_address.required_with' => 'La dirección de destino es obligatoria.',
            'items.*.new_shipment.package_type.required_with' => 'El tipo de paquete es obligatorio.',
            'items.*.new_shipment.weight_lb.required_with' => 'El peso en libras es obligatorio.',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $items = $this->input('items', []);

            if (! is_array($items) || empty($items)) {
                return;
            }

            // Validar que cada item tenga shipment_id o new_shipment
            foreach ($items as $index => $item) {
                $hasExisting = ! empty($item['shipment_id'] ?? null);
                $hasNew = ! empty($item['new_shipment'] ?? null);

                if (! $hasExisting && ! $hasNew) {
                    $validator->errors()->add(
                        "items.{$index}",
                        'La parada #'.($index + 1).' debe especificar un paquete existente o los datos de un nuevo paquete.'
                    );
                }

                if ($hasNew && empty($item['new_shipment']['sender_id']) && empty($item['new_shipment']['recipient_name'])) {
                    $validator->errors()->add(
                        "items.{$index}.new_shipment.sender_id",
                        'La parada #'.($index + 1).' debe especificar un cliente remitente o el nombre del destinatario.'
                    );
                }
            }

            // Validar regla de prioridad: alta (1) <= media (2) <= baja (3) según stop_order
            $rankings = [
                ShipmentTaskItem::PRIORITY_ALTA => 1,
                ShipmentTaskItem::PRIORITY_MEDIA => 2,
                ShipmentTaskItem::PRIORITY_BAJA => 3,
                'ALTA' => 1,
                'MEDIA' => 2,
                'BAJA' => 3,
                'alta' => 1,
                'media' => 2,
                'baja' => 3,
            ];

            // Ordenar items por stop_order
            $sortedItems = $items;
            usort($sortedItems, fn ($a, $b) => ($a['stop_order'] ?? 0) <=> ($b['stop_order'] ?? 0));

            $maxPriorityRankSeen = 1;
            foreach ($sortedItems as $item) {
                $priority = $item['priority'] ?? ShipmentTaskItem::PRIORITY_MEDIA;
                $currentRank = $rankings[$priority] ?? 2;

                if ($currentRank < $maxPriorityRankSeen) {
                    $validator->errors()->add(
                        'items',
                        'Violación en las prioridades: las entregas de mayor prioridad (ej. Alta) deben programarse antes que las de menor prioridad (ej. Media o Baja).'
                    );
                    break;
                }

                $maxPriorityRankSeen = max($maxPriorityRankSeen, $currentRank);
            }
        });
    }
}
