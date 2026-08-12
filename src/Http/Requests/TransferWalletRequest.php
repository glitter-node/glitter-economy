<?php

namespace Modules\Glitter\Economy\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TransferWalletRequest extends FormRequest
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
            'to_wallet_id' => ['required', 'integer', 'min:1'],
            'amount' => ['required', 'string', 'regex:/^[0-9]{1,16}(?:\.[0-9]{1,2})?\z/', 'not_regex:/^0+(?:\.0{1,2})?\z/'],
            'context' => ['sometimes', 'array'],
        ];
    }
}
