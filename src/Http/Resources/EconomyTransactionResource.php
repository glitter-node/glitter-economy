<?php

namespace Modules\Glitter\Economy\Http\Resources;

use App\Http\Resources\BaseApiResource;
use Illuminate\Http\Request;

class EconomyTransactionResource extends BaseApiResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'wallet_id' => $this->wallet_id,
            'related_wallet_id' => $this->related_wallet_id,
            'reversed_transaction_id' => $this->reversed_transaction_id,
            'transaction_type' => $this->transaction_type?->value,
            'amount' => $this->amount,
            'balance_after' => $this->balance_after,
            'locked_balance_after' => $this->locked_balance_after,
            'available_balance_after' => $this->available_balance_after,
            'source_module' => $this->source_module,
            'source_type' => $this->source_type,
            'source_id' => $this->source_id,
            'correlation_id' => $this->correlation_id,
            'description' => $this->description,
            'metadata' => $this->metadata,
            'wallet' => $this->whenLoaded('wallet', fn () => [
                'id' => $this->wallet?->id,
                'user_id' => $this->wallet?->user_id,
                'wallet_type' => $this->wallet?->wallet_type,
                'currency_code' => $this->wallet?->currency_code,
            ]),
            'occurred_at' => $this->occurred_at?->toIso8601String(),
            ...$this->formatTimestamps(),
        ];
    }
}
