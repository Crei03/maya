<?php

declare(strict_types=1);

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class FilterDriverRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'search'       => ['nullable', 'string'],
            'is_available' => ['nullable', 'string', 'in:0,1,true,false'],
            'status'       => ['nullable', 'string', 'in:0,1,true,false'],
            'per_page'     => ['nullable', 'integer'],
        ];
    }
}
