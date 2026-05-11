<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreDriverRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_id'           => ['required', 'string', 'exists:users,id'],
            'phone'             => ['nullable', 'string', 'max:30'],
            'license_number'    => ['nullable', 'string', 'max:50'],
            'license_expiry'    => ['nullable', 'date'],
            'emergency_contact' => ['nullable', 'string', 'max:120'],
            'emergency_phone'   => ['nullable', 'string', 'max:30'],
            'is_available'      => ['nullable', 'boolean'],
            'status'            => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'user_id.required' => 'Debes seleccionar un usuario.',
            'user_id.exists'   => 'El usuario seleccionado no existe.',
        ];
    }

    public function attributes(): array
    {
        return [
            'phone'             => 'teléfono',
            'license_number'    => 'número de licencia',
            'license_expiry'    => 'vencimiento de licencia',
            'emergency_contact' => 'contacto de emergencia',
            'emergency_phone'   => 'teléfono de emergencia',
        ];
    }
}
