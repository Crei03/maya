<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $userId = $this->route('id');

        return [
            'name' => ['required', 'string', 'max:120'],
            'email' => ['required', 'email', Rule::unique('users', 'email')->ignore($userId)],

            'password' => ['nullable', 'string', 'min:8'],
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
