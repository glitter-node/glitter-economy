<?php

namespace Modules\Glitter\Economy\Repositories;

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Modules\Glitter\Economy\Contracts\Repositories\EconomyHoldRepositoryInterface;
use Modules\Glitter\Economy\Enums\EconomyHoldStatus;
use Modules\Glitter\Economy\Exceptions\EconomyTableUnavailableException;
use Modules\Glitter\Economy\Exceptions\HoldNotFoundException;
use Modules\Glitter\Economy\Models\EconomyHold;

class EconomyHoldRepository implements EconomyHoldRepositoryInterface
{
    public function __construct(
        protected EconomyHold $model
    ) {}

    public function create(array $data): EconomyHold
    {
        $this->assertTableExists();

        return $this->model->create(array_merge([
            'uuid' => (string) Str::uuid(),
        ], $data));
    }

    public function findActiveById(int $id): ?EconomyHold
    {
        if (! $this->tableExists()) {
            return null;
        }

        return $this->model->newQuery()
            ->whereKey($id)
            ->where('hold_status', EconomyHoldStatus::Active->value)
            ->first();
    }

    public function findByIdForUpdate(int $id): ?EconomyHold
    {
        if (! $this->tableExists()) {
            return null;
        }

        return $this->model->newQuery()
            ->whereKey($id)
            ->lockForUpdate()
            ->first();
    }

    public function updateStatus(EconomyHold $hold, EconomyHoldStatus $status, array $data = []): EconomyHold
    {
        $this->assertTableExists();

        $hold->update(array_merge($data, ['hold_status' => $status->value]));

        $fresh = $hold->fresh();
        if ($fresh instanceof EconomyHold) {
            return $fresh;
        }

        return $hold;
    }

    public function release(int $id, array $data = []): EconomyHold
    {
        return $this->updateActiveStatusById($id, EconomyHoldStatus::Released, $data);
    }

    public function void(int $id, array $data = []): EconomyHold
    {
        return $this->updateActiveStatusById($id, EconomyHoldStatus::Cancelled, $data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    protected function updateActiveStatusById(int $id, EconomyHoldStatus $status, array $data): EconomyHold
    {
        $this->assertTableExists();

        $hold = $this->findActiveById($id);
        if ($hold === null) {
            throw new HoldNotFoundException($id);
        }

        return $this->updateStatus($hold, $status, $data);
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
