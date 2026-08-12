<?php

namespace Modules\Glitter\Economy\Http\Requests;

use App\Models\User;
use Illuminate\Validation\Rule;

class AdminCreditWalletRequest extends CreditWalletRequest
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
        return array_merge(parent::rules(), [
            'user_id' => ['required', 'integer', Rule::exists(User::class, 'id')],
            'memo' => ['nullable', 'string', 'max:500'],
        ]);
    }
}
