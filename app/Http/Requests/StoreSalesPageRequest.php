<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreSalesPageRequest extends FormRequest
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
            'product_name' => ['required', 'string', 'max:255'],
            'raw_input' => ['required', 'array'],
            'ai_output' => ['required', 'array'],
            'theme' => ['nullable', 'string', 'max:255'],
        ];
    }
}
