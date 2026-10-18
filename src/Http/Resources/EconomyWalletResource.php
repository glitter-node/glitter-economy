<?php

namespace Modules\Glitter\Economy\Http\Resources;

use App\Http\Resources\BaseApiResource;
use Illuminate\Http\Request;

class EconomyWalletResource extends BaseApiResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'user_id' => $this->user_id,
            'wallet_type' => $this->wallet_type,
            'currency_code' => $this->currency_code,
            'balance' => $this->balance,
            'locked_balance' => $this->locked_balance,
            'available_balance' => $this->available_balance,
            'status' => $this->status,
            'metadata' => $this->metadata,
            'user' => $this->whenLoaded('user', fn () => [
                'id' => $this->user?->id,
                'name' => $this->user?->name,
                'nickname' => $this->user?->nickname,
                'email' => $this->user?->email,
            ]),
            ...$this->formatTimestamps(),
        ];
    }
}
