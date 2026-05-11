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
            'name'              => ['required', 'string', 'max:120'],
            'email'             => ['required', 'email', 'unique:users,email'],
            'phone'             => ['nullable', 'string', 'max:30'],
            'password'          => ['required', 'string', 'min:8'],
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
            'name.required'     => 'El nombre del conductor es obligatorio.',
            'email.required'    => 'El correo electrónico es obligatorio.',
            'email.email'       => 'El correo debe tener un formato válido.',
            'email.unique'      => 'El correo ya está registrado.',
            'password.required' => 'La contraseña es obligatoria.',
            'password.min'      => 'La contraseña debe tener al menos 8 caracteres.',
        ];
    }

    public function attributes(): array
    {
        return [
            'name'              => 'nombre',
            'email'             => 'correo',
            'phone'             => 'teléfono',
            'password'          => 'contraseña',
            'license_number'    => 'número de licencia',
            'license_expiry'    => 'vencimiento de licencia',
            'emergency_contact' => 'contacto de emergencia',
            'emergency_phone'   => 'teléfono de emergencia',
        ];
    }
}
