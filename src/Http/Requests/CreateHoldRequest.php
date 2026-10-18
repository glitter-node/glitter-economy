<?php

namespace Modules\Glitter\Economy\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreateHoldRequest extends FormRequest
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
            'amount' => ['required', 'string', 'regex:/^[0-9]{1,16}(?:\.[0-9]{1,2})?\z/', 'not_regex:/^0+(?:\.0{1,2})?\z/'],
            'reason' => ['nullable', 'string', 'max:100'],
            'source_module' => ['nullable', 'string', 'max:100'],
            'source_type' => ['nullable', 'string', 'max:100'],
            'source_id' => ['nullable', 'string', 'max:100'],
            'correlation_id' => ['nullable', 'string', 'max:100'],
            'expires_at' => ['nullable', 'date', 'after:now'],
            'metadata' => ['sometimes', 'array'],
        ];
    }
}
