<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class FilterShipmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'search'       => ['string', 'nullable'],
            'status'       => ['string', 'nullable'],
            'warehouse_id' => ['string', 'nullable'],
            'driver_id'    => ['integer', 'nullable'],
            'date_from'    => ['date', 'nullable'],
            'date_to'      => ['date', 'nullable', 'after_or_equal:date_from'],
            'package_type' => ['string', 'nullable'],
            'per_page'     => ['integer', 'nullable', 'min:1', 'max:100'],
        ];
    }
}
