<?php

namespace Modules\Glitter\Economy\DTO;

final readonly class CreditRequestData
{
    /**
     * @param  array<string, mixed>  $context
     */
    public function __construct(
        public int $walletId,
        public string $amount,
        public array $context = [],
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'wallet_id' => $this->walletId,
            'amount' => $this->amount,
            'context' => $this->context,
        ];
    }
}
