<?php

namespace App\Http\Requests\Api\Audio;

use Illuminate\Foundation\Http\FormRequest;

class IndexAudioRequest extends FormRequest
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
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'at' => ['sometimes', 'date'],
        ];
    }

    /**
     * Get the 'page' parameter from the request.
     */
    public function pageNumber(): int
    {
        return $this->integer('page', 1);
    }

    /**
     * Get the 'per_page' parameter from the request.
     */
    public function perPage(): int
    {
        return $this->integer('per_page', 10);
    }

    /**
     * Get the 'at' parameter from the request.
     */
    public function at(): ?string
    {
        return $this->input('at');
    }
}
