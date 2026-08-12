<?php

namespace Modules\Glitter\Economy\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReleaseHoldRequest extends FormRequest
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
            'context' => ['sometimes', 'array'],
        ];
    }
}
