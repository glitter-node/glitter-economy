<?php

namespace Modules\Glitter\Economy\DTO;

use Carbon\CarbonInterface;

final readonly class HoldRequestData
{
    /**
     * @param  array<string, mixed>  $metadata
     */
    public function __construct(
        public int $walletId,
        public string $amount,
        public ?string $reason = null,
        public ?string $sourceModule = null,
        public ?string $sourceType = null,
        public ?string $sourceId = null,
        public ?string $correlationId = null,
        public ?CarbonInterface $expiresAt = null,
        public array $metadata = [],
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'wallet_id' => $this->walletId,
            'amount' => $this->amount,
            'reason' => $this->reason,
            'source_module' => $this->sourceModule,
            'source_type' => $this->sourceType,
            'source_id' => $this->sourceId,
            'correlation_id' => $this->correlationId,
            'expires_at' => $this->expiresAt?->toIso8601String(),
            'metadata' => $this->metadata,
        ];
    }
}
