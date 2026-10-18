<?php

namespace Modules\Glitter\Economy\Contracts\Repositories;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Glitter\Economy\Models\EconomyTransaction;

interface EconomyTransactionRepositoryInterface
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): EconomyTransaction;

    /**
     * @param  array<string, mixed>  $data
     */
    public function createLedgerEntry(array $data): EconomyTransaction;

    public function findByUuid(string $uuid): ?EconomyTransaction;

    /**
     * @param  array<string, mixed>  $filters
     * @return Collection<int, EconomyTransaction>
     */
    public function listTransactions(array $filters = []): Collection;

    /**
     *   array<string, mixed>  $filters
     */
    public function paginateForAdmin(array $filters = [], int $perPage = 20): LengthAwarePaginator;
}
