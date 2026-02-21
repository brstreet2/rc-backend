<?php

namespace App\Http\Requests\Api\Promotion;

use Illuminate\Foundation\Http\FormRequest;

class CreatePromotionRequest extends FormRequest
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
            'audio_id' => ['required', 'integer', 'exists:audio,id'],
            'network_id' => ['nullable', 'integer'],
            'mformat' => ['nullable', 'string'],
            'channel_id' => ['nullable', 'integer'],
            'priority' => ['required', 'integer'],
            'version' => ['required', 'integer', 'min:1'],
            'visible' => ['required', 'boolean'],
            'start_at' => ['required', 'date'],
            'end_at' => ['required', 'date', 'after_or_equal:start_at'],
        ];
    }
}
