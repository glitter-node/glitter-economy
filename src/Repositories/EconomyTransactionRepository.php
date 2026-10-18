<?php

namespace Modules\Glitter\Economy\Repositories;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Modules\Glitter\Economy\Contracts\Repositories\EconomyTransactionRepositoryInterface;
use Modules\Glitter\Economy\Exceptions\EconomyTableUnavailableException;
use Modules\Glitter\Economy\Models\EconomyTransaction;

class EconomyTransactionRepository implements EconomyTransactionRepositoryInterface
{
    public function __construct(
        protected EconomyTransaction $model
    ) {}

    public function create(array $data): EconomyTransaction
    {
        return $this->createLedgerEntry($data);
    }

    public function createLedgerEntry(array $data): EconomyTransaction
    {
        $this->assertTableExists();

        return $this->model->create(array_merge([
            'uuid' => (string) Str::uuid(),
        ], $data));
    }

    public function findByUuid(string $uuid): ?EconomyTransaction
    {
        if (! $this->tableExists()) {
            return null;
        }

        return $this->model->newQuery()
            ->where('uuid', $uuid)
            ->first();
    }

    public function listTransactions(array $filters = []): Collection
    {
        if (! $this->tableExists()) {
            return new Collection();
        }

        $query = $this->model->newQuery();

        if (isset($filters['wallet_id'])) {
            $query->where('wallet_id', (int) $filters['wallet_id']);
        }

        if (isset($filters['user_id'])) {
            $query->whereHas('wallet', fn ($walletQuery) => $walletQuery->where('user_id', (int) $filters['user_id']));
        }

        if (isset($filters['transaction_type'])) {
            $query->where('transaction_type', (string) $filters['transaction_type']);
        }

        if (isset($filters['correlation_id'])) {
            $query->where('correlation_id', (string) $filters['correlation_id']);
        }

        return $query->orderByDesc('occurred_at')->orderByDesc('id')->get();
    }

    public function paginateForAdmin(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $perPage = max(1, min(100, $perPage));

        if (! $this->tableExists()) {
            return new LengthAwarePaginator([], 0, $perPage);
        }

        return $this->adminQuery($filters)
            ->with('wallet:id,user_id,wallet_type,currency_code')
            ->orderByDesc('occurred_at')
            ->orderByDesc('id')
            ->paginate($perPage);
    }

    /**
     *   array<string, mixed>  $filters
     *  Builder<EconomyTransaction>
     */
    private function adminQuery(array $filters): Builder
    {
        $query = $this->model->newQuery();

        if (! empty($filters['wallet_id'])) {
            $query->where('wallet_id', (int) $filters['wallet_id']);
        }

        if (! empty($filters['user_id'])) {
            $query->whereHas('wallet', fn (Builder $walletQuery) => $walletQuery->where('user_id', (int) $filters['user_id']));
        }

        $type = $filters['type'] ?? $filters['transaction_type'] ?? null;
        if (! empty($type)) {
            $query->where('transaction_type', (string) $type);
        }

        if (! empty($filters['reference_type'])) {
            $query->where('source_type', (string) $filters['reference_type']);
        }

        if (! empty($filters['reference_id'])) {
            $query->where('source_id', (string) $filters['reference_id']);
        }

        if (! empty($filters['source_module'])) {
            $query->where('source_module', (string) $filters['source_module']);
        }

        if (! empty($filters['correlation_id'])) {
            $query->where('correlation_id', (string) $filters['correlation_id']);
        }

        if (! empty($filters['date_from'])) {
            $query->where('occurred_at', '>=', CarbonImmutable::parse((string) $filters['date_from'])->startOfDay());
        }

        if (! empty($filters['date_to'])) {
            $query->where('occurred_at', '<=', CarbonImmutable::parse((string) $filters['date_to'])->endOfDay());
        }

        return $query;
    }

    protected function tableExists(): bool
    {
        return Schema::hasTable($this->model->getTable());
    }

    protected function assertTableExists(): void
    {
        if (! $this->tableExists()) {
            throw new EconomyTableUnavailableException($this->model->getTable());
        }
    }
}
