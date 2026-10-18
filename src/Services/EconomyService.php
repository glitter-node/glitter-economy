<?php

namespace Modules\Glitter\Economy\Services;

use Illuminate\Database\Eloquent\Collection;
use Modules\Glitter\Economy\DTO\CreditRequestData;
use Modules\Glitter\Economy\DTO\DebitRequestData;
use Modules\Glitter\Economy\DTO\HoldRequestData;
use Modules\Glitter\Economy\DTO\ReleaseHoldRequestData;
use Modules\Glitter\Economy\DTO\TransferRequestData;
use Modules\Glitter\Economy\Models\EconomyHold;
use Modules\Glitter\Economy\Models\EconomyTransaction;
use Modules\Glitter\Economy\Models\EconomyWallet;

class EconomyService
{
    public function __construct(
        protected WalletService $walletService,
        protected HoldService $holdService,
    ) {}

    public function findWalletByUserId(int $userId, string $walletType = 'default', string $currencyCode = 'POINT'): ?EconomyWallet
    {
        return $this->walletService->findWalletByUserId($userId, $walletType, $currencyCode);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function createWalletForUser(int $userId, array $attributes = []): EconomyWallet
    {
        return $this->walletService->createWalletForUser($userId, $attributes);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function getOrCreateWalletForUser(int $userId, array $attributes = []): EconomyWallet
    {
        return $this->walletService->getOrCreateWalletForUser($userId, $attributes);
    }

    public function credit(CreditRequestData $data): EconomyTransaction
    {
        return $this->walletService->credit($data);
    }

    public function debit(DebitRequestData $data): EconomyTransaction
    {
        return $this->walletService->debit($data);
    }

    /**
     * @return array{out: EconomyTransaction, in: EconomyTransaction}
     */
    public function transfer(TransferRequestData $data): array
    {
        return $this->walletService->transfer($data);
    }

    public function createHold(HoldRequestData $data): EconomyHold
    {
        return $this->holdService->createHold($data);
    }

    public function releaseHold(ReleaseHoldRequestData $data): EconomyHold
    {
        return $this->holdService->releaseHold($data);
    }

    public function voidHold(ReleaseHoldRequestData $data): EconomyHold
    {
        return $this->holdService->voidHold($data);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Collection<int, EconomyTransaction>
     */
    public function listTransactions(array $filters = []): Collection
    {
        return $this->walletService->listTransactions($filters);
    }
}
