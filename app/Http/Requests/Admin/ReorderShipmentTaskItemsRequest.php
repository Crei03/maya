<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use App\Models\ShipmentTaskItem;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class ReorderShipmentTaskItemsRequest extends FormRequest
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
            'items' => ['required', 'array', 'min:1'],
            'items.*.id' => ['required', 'string', 'exists:shipment_task_items,id'],
            'items.*.stop_order' => ['required', 'integer', 'min:1'],
            'items.*.priority' => ['required', 'string', 'in:'.implode(',', ShipmentTaskItem::PRIORITIES)],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $items = $this->input('items', []);

            if (! is_array($items)) {
                return;
            }

            $rankings = [
                ShipmentTaskItem::PRIORITY_ALTA => 1,
                ShipmentTaskItem::PRIORITY_MEDIA => 2,
                ShipmentTaskItem::PRIORITY_BAJA => 3,
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
