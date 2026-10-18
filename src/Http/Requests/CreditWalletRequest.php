<?php

namespace Modules\Glitter\Economy\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreditWalletRequest extends FormRequest
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
            'context' => ['sometimes', 'array'],
        ];
    }
}
