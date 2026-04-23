<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreClientRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'nombre' => ['required', 'string', 'max:120'],
            'apellido' => ['required', 'string', 'max:120'],
            'residencia_id' => ['nullable', 'integer', 'exists:catalogo_valores,id'],
            'provincia_id' => ['nullable', 'integer'],
            'distrito_id' => ['nullable', 'integer'],
            'corregimiento_id' => ['nullable', 'integer'],
            'calle' => ['nullable', 'string', 'max:120'],
            'numero' => ['required', 'string', 'max:40'],
            'codigo_postal' => ['nullable', 'string', 'max:20'],
        ];
    }

    /**
     * Get custom validation messages.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'nombre.required' => 'El campo nombre es obligatorio.',
            'apellido.required' => 'El campo apellido es obligatorio.',
            'numero.required' => 'El campo numero es obligatorio.',
            'residencia_id.integer' => 'El campo residencia debe ser un valor valido.',
            'residencia_id.exists' => 'El campo residencia debe existir en el catalogo.',
            'provincia_id.integer' => 'El campo provincia debe ser un valor valido.',
            'distrito_id.integer' => 'El campo distrito debe ser un valor valido.',
            'corregimiento_id.integer' => 'El campo corregimiento debe ser un valor valido.',
            'calle.string' => 'El campo calle debe ser texto.',
            'calle.max' => 'El campo calle no debe superar 120 caracteres.',
            'codigo_postal.string' => 'El campo codigo postal debe ser texto.',
            'codigo_postal.max' => 'El campo codigo postal no debe superar 20 caracteres.',
        ];
    }

    /**
     * Get custom validation attributes.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'nombre' => 'nombre',
            'apellido' => 'apellido',
            'residencia_id' => 'residencia',
            'provincia_id' => 'provincia',
            'distrito_id' => 'distrito',
            'corregimiento_id' => 'corregimiento',
            'calle' => 'calle',
            'numero' => 'numero',
            'codigo_postal' => 'codigo postal',
        ];
    }
}
