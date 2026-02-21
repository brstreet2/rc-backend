<?php

namespace App\Http\Requests\Api\Promotion;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePromotionRequest extends FormRequest
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
            'audio_id' => ['sometimes', 'integer', 'exists:audio,id'],
            'network_id' => ['sometimes', 'nullable', 'integer'],
            'mformat' => ['sometimes', 'nullable', 'string'],
            'channel_id' => ['sometimes', 'nullable', 'integer'],
            'priority' => ['sometimes', 'integer'],
            'version' => ['sometimes', 'integer', 'min:1'],
            'visible' => ['sometimes', 'boolean'],
            'start_at' => ['sometimes', 'date'],
            'end_at' => ['sometimes', 'date', 'after_or_equal:start_at'],
        ];
    }
}
