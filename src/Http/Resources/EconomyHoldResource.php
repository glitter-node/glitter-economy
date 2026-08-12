<?php

namespace Modules\Glitter\Economy\Http\Resources;

use App\Http\Resources\BaseApiResource;
use Illuminate\Http\Request;

class EconomyHoldResource extends BaseApiResource
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
            'hold_status' => $this->hold_status?->value,
            'amount' => $this->amount,
            'reason' => $this->reason,
            'source_module' => $this->source_module,
            'source_type' => $this->source_type,
            'source_id' => $this->source_id,
            'correlation_id' => $this->correlation_id,
            'metadata' => $this->metadata,
            'created_by' => $this->created_by,
            'released_by' => $this->released_by,
            'expires_at' => $this->expires_at?->toIso8601String(),
            'released_at' => $this->released_at?->toIso8601String(),
            'captured_at' => $this->captured_at?->toIso8601String(),
            ...$this->formatTimestamps(),
        ];
    }
}
