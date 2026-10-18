<?php

namespace Modules\Glitter\Economy\Repositories;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Modules\Glitter\Economy\Contracts\Repositories\EconomyWalletRepositoryInterface;
use Modules\Glitter\Economy\Exceptions\EconomyException;
use Modules\Glitter\Economy\Exceptions\EconomyTableUnavailableException;
use Modules\Glitter\Economy\Models\EconomyWallet;

class EconomyWalletRepository implements EconomyWalletRepositoryInterface
{
    public function __construct(
        protected EconomyWallet $model
    ) {}

    public function findById(int $id): ?EconomyWallet
    {
        if (! $this->tableExists()) {
            return null;
        }

        return $this->model->newQuery()->find($id);
    }

    public function findByIdForUpdate(int $id): ?EconomyWallet
    {
        if (! $this->tableExists()) {
            return null;
        }

        return $this->model->newQuery()
            ->whereKey($id)
            ->lockForUpdate()
            ->first();
    }

    public function findByUserId(int $userId, string $walletType = 'default', string $currencyCode = 'POINT'): ?EconomyWallet
    {
        if (! $this->tableExists()) {
            return null;
        }

        return $this->model->newQuery()
            ->where('user_id', $userId)
            ->where('wallet_type', $walletType)
            ->where('currency_code', $currencyCode)
            ->first();
    }

    public function findByUserIdForUpdate(int $userId): ?EconomyWallet
    {
        if (! $this->tableExists()) {
            return null;
        }

        return $this->model->newQuery()
            ->where('user_id', $userId)
            ->orderBy('id')
            ->lockForUpdate()
            ->first();
    }

    public function createForUser(int $userId, array $attributes = []): EconomyWallet
    {
        $this->assertTableExists();

        return $this->model->create(array_merge([
            'uuid' => (string) Str::uuid(),
            'user_id' => $userId,
            'wallet_type' => 'default',
            'currency_code' => 'POINT',
            'balance' => '0.00',
            'locked_balance' => '0.00',
            'available_balance' => '0.00',
            'status' => 'active',
        ], $attributes));
    }

    public function getOrCreateForUser(int $userId, array $attributes = []): EconomyWallet
    {
        $this->assertTableExists();

        $unique = [
            'user_id' => $userId,
            'wallet_type' => (string) ($attributes['wallet_type'] ?? 'default'),
            'currency_code' => (string) ($attributes['currency_code'] ?? 'POINT'),
        ];
        $values = array_merge([
            'uuid' => (string) Str::uuid(),
            'balance' => '0.00',
            'locked_balance' => '0.00',
            'available_balance' => '0.00',
            'status' => 'active',
        ], $attributes);
        unset($values['user_id'], $values['wallet_type'], $values['currency_code']);

        return $this->model->newQuery()->createOrFirst($unique, $values);
    }

    public function updateBalances(EconomyWallet $wallet, string $balance, string $lockedBalance): EconomyWallet
    {
        $this->assertTableExists();

        $wallet->update([
            'balance' => $balance,
            'locked_balance' => $lockedBalance,
            'available_balance' => $this->subtractDecimal($balance, $lockedBalance),
        ]);

        $fresh = $wallet->fresh();
        if ($fresh instanceof EconomyWallet) {
            return $fresh;
        }

        return $wallet;
    }

    public function paginateForAdmin(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $perPage = max(1, min(100, $perPage));

        if (! $this->tableExists()) {
            return new LengthAwarePaginator([], 0, $perPage);
        }

        return $this->adminQuery($filters)
            ->with('user:id,name,nickname,email')
            ->orderByDesc('id')
            ->paginate($perPage);
    }

    public function summarizeForAdmin(array $filters = []): array
    {
        if (! $this->tableExists()) {
            return [
                'total_wallets' => 0,
                'total_balance' => '0.00',
                'total_locked_balance' => '0.00',
            ];
        }

        $query = $this->adminQuery($filters);

        return [
            'total_wallets' => (int) (clone $query)->count(),
            'total_balance' => number_format((float) (clone $query)->sum('balance'), 2, '.', ''),
            'total_locked_balance' => number_format((float) (clone $query)->sum('locked_balance'), 2, '.', ''),
        ];
    }

    /**
     *   array<string, mixed>  $filters
     *  Builder<EconomyWallet>
     */
    private function adminQuery(array $filters): Builder
    {
        $query = $this->model->newQuery();

        if (! empty($filters['user_id'])) {
            $query->where('user_id', (int) $filters['user_id']);
        }

        if (! empty($filters['wallet_type'])) {
            $query->where('wallet_type', (string) $filters['wallet_type']);
        }

        if (! empty($filters['currency_code'])) {
            $query->where('currency_code', (string) $filters['currency_code']);
        }

        if (! empty($filters['status'])) {
            $query->where('status', (string) $filters['status']);
        }

        if (! empty($filters['search'])) {
            $search = trim((string) $filters['search']);
            $query->where(function (Builder $searchQuery) use ($search): void {
                if (ctype_digit($search)) {
                    $searchQuery->orWhere('user_id', (int) $search)
                        ->orWhere('id', (int) $search);
                }

                $searchQuery->orWhereHas('user', function (Builder $userQuery) use ($search): void {
                    $userQuery->where('email', 'like', '%'.$search.'%')
                        ->orWhere('name', 'like', '%'.$search.'%')
                        ->orWhere('nickname', 'like', '%'.$search.'%');
                });
            });
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

    private function subtractDecimal(string $left, string $right): string
    {
        if (! function_exists('bcsub')) {
            throw new EconomyException('glitter-economy::messages.exceptions.decimal_math_unavailable');
        }

        return bcsub($left, $right, 2);
    }
}
