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
            'raw_input.description' => ['required', 'string'],
            'raw_input.key_features' => ['required', 'array', 'min:1'],
            'raw_input.key_features.*' => ['required', 'string'],
            'raw_input.target_audience' => ['required', 'string'],
            'raw_input.price' => ['required', 'string'],
            'raw_input.usp' => ['required', 'string'],
            'ai_output' => ['required', 'array'],
            'ai_output.hero' => ['required', 'array'],
            'ai_output.hero.headline' => ['required', 'string'],
            'ai_output.hero.subheadline' => ['required', 'string'],
            'ai_output.benefits' => ['required', 'array'],
            'ai_output.benefits.*.title' => ['required', 'string'],
            'ai_output.benefits.*.description' => ['required', 'string'],
            'ai_output.features' => ['required', 'array', 'min:1'],
            'ai_output.features.*' => ['required', 'string'],
            'ai_output.social_proof' => ['required', 'array'],
            'ai_output.social_proof.*.name' => ['required', 'string'],
            'ai_output.social_proof.*.review' => ['required', 'string'],
            'ai_output.pricing' => ['required', 'array'],
            'ai_output.pricing.price_text' => ['required', 'string'],
            'ai_output.pricing.call_to_action_text' => ['required', 'string'],
            'ai_output.pricing.guarantee' => ['required', 'string'],
            'theme' => ['nullable', 'string', 'max:255'],
        ];
    }
}
