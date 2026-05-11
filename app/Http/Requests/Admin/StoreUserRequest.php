<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', 'unique:users,email'],

            'password' => ['required', 'string', 'min:8'],
            'role' => ['nullable', 'string', 'in:gestor,messenger'],
            'status' => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El campo usuario es obligatorio.',
            'email.required' => 'El campo correo es obligatorio.',
            'email.email' => 'El correo debe tener un formato válido.',
            'email.unique' => 'El correo ya está registrado.',
            'password.required' => 'El campo contraseña es obligatorio.',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'usuario',
            'email' => 'correo',
            'password' => 'contraseña',
        ];
    }
}
