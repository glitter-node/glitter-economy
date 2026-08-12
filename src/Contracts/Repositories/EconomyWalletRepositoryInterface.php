<?php

namespace Modules\Glitter\Economy\Contracts\Repositories;

use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Glitter\Economy\Models\EconomyWallet;

interface EconomyWalletRepositoryInterface
{
    public function findById(int $id): ?EconomyWallet;

    public function findByIdForUpdate(int $id): ?EconomyWallet;

    public function findByUserId(int $userId, string $walletType = 'default', string $currencyCode = 'POINT'): ?EconomyWallet;

    public function findByUserIdForUpdate(int $userId): ?EconomyWallet;

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function createForUser(int $userId, array $attributes = []): EconomyWallet;

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function getOrCreateForUser(int $userId, array $attributes = []): EconomyWallet;

    public function updateBalances(EconomyWallet $wallet, string $balance, string $lockedBalance): EconomyWallet;

    /**
     *   array<string, mixed>  $filters
     */
    public function paginateForAdmin(array $filters = [], int $perPage = 20): LengthAwarePaginator;

    /**
     *   array<string, mixed>  $filters
     *  array{total_wallets:int,total_balance:string,total_locked_balance:string}
     */
    public function summarizeForAdmin(array $filters = []): array;
}
