<?php

namespace Modules\Glitter\Economy\DTO;

final readonly class TransferRequestData
{
    /**
     * @param  array<string, mixed>  $context
     */
    public function __construct(
        public int $fromWalletId,
        public int $toWalletId,
        public string $amount,
        public array $context = [],
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'from_wallet_id' => $this->fromWalletId,
            'to_wallet_id' => $this->toWalletId,
            'amount' => $this->amount,
            'context' => $this->context,
        ];
    }
}
