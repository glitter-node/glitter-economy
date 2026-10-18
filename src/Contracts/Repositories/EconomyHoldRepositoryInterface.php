<?php

namespace Modules\Glitter\Economy\Contracts\Repositories;

use Modules\Glitter\Economy\Enums\EconomyHoldStatus;
use Modules\Glitter\Economy\Models\EconomyHold;

interface EconomyHoldRepositoryInterface
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): EconomyHold;

    public function findActiveById(int $id): ?EconomyHold;

    public function findByIdForUpdate(int $id): ?EconomyHold;

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateStatus(EconomyHold $hold, EconomyHoldStatus $status, array $data = []): EconomyHold;

    /**
     * @param  array<string, mixed>  $data
     */
    public function release(int $id, array $data = []): EconomyHold;

    /**
     * @param  array<string, mixed>  $data
     */
    public function void(int $id, array $data = []): EconomyHold;
}
