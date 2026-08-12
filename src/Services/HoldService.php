<?php

namespace Modules\Glitter\Economy\Services;

use App\Extension\HookManager;
use Illuminate\Support\Facades\DB;
use Modules\Glitter\Economy\Contracts\Repositories\EconomyHoldRepositoryInterface;
use Modules\Glitter\Economy\Contracts\Repositories\EconomyTransactionRepositoryInterface;
use Modules\Glitter\Economy\Contracts\Repositories\EconomyWalletRepositoryInterface;
use Modules\Glitter\Economy\DTO\HoldRequestData;
use Modules\Glitter\Economy\DTO\ReleaseHoldRequestData;
use Modules\Glitter\Economy\Enums\EconomyHoldStatus;
use Modules\Glitter\Economy\Enums\EconomyTransactionType;
use Modules\Glitter\Economy\Exceptions\EconomyException;
use Modules\Glitter\Economy\Exceptions\HoldNotFoundException;
use Modules\Glitter\Economy\Exceptions\HoldStateException;
use Modules\Glitter\Economy\Exceptions\InsufficientBalanceException;
use Modules\Glitter\Economy\Exceptions\InvalidEconomyAmountException;
use Modules\Glitter\Economy\Exceptions\WalletNotFoundException;
use Modules\Glitter\Economy\Models\EconomyHold;
use Modules\Glitter\Economy\Models\EconomyWallet;

class HoldService
{
    public function __construct(
        protected EconomyHoldRepositoryInterface $holds,
        protected EconomyWalletRepositoryInterface $wallets,
        protected EconomyTransactionRepositoryInterface $transactions,
        protected EconomySettingsService $settings,
    ) {}

    public function create(HoldRequestData $data): EconomyHold
    {
        return $this->createHold($data);
    }

    public function createHold(HoldRequestData $data): EconomyHold
    {
        return DB::transaction(function () use ($data) {
            $amount = $this->normalizePositiveAmount($data->amount);
            $wallet = $this->lockWallet($data->walletId);

            HookManager::doAction('glitter-economy.hold.before_create', $wallet, $data);

            $balance = $this->decimalValue($wallet->balance);
            $lockedBalance = $this->decimalValue($wallet->locked_balance);
            $availableBalance = $this->subtractDecimal($balance, $lockedBalance);
            $this->assertAvailableBalance($availableBalance, $amount);

            $newLockedBalance = $this->addDecimal($lockedBalance, $amount);
            $newAvailableBalance = $this->subtractDecimal($balance, $newLockedBalance);
            $this->assertNonNegativeAvailableBalance($newAvailableBalance);

            $hold = $this->holds->create([
                'wallet_id' => $wallet->id,
                'hold_status' => EconomyHoldStatus::Active,
                'amount' => $amount,
                'reason' => $data->reason,
                'source_module' => $data->sourceModule,
                'source_type' => $data->sourceType,
                'source_id' => $data->sourceId,
                'correlation_id' => $data->correlationId,
                'metadata' => $data->metadata,
                'created_by' => $data->metadata['created_by'] ?? null,
                'expires_at' => $data->expiresAt,
            ]);

            $this->transactions->createLedgerEntry($this->ledgerData(
                wallet: $wallet,
                hold: $hold,
                type: EconomyTransactionType::Hold,
                amount: $amount,
                balanceAfter: $balance,
                lockedBalanceAfter: $newLockedBalance,
                availableBalanceAfter: $newAvailableBalance,
                context: $data->metadata,
            ));

            $updatedWallet = $this->wallets->updateBalances($wallet, $balance, $newLockedBalance);

            HookManager::doAction('glitter-economy.hold.after_create', $hold, $updatedWallet, $data);

            return $hold;
        });
    }

    public function release(ReleaseHoldRequestData $data): EconomyHold
    {
        return $this->releaseHold($data);
    }

    public function releaseHold(ReleaseHoldRequestData $data): EconomyHold
    {
        return $this->releaseOrVoid($data, EconomyHoldStatus::Released, EconomyTransactionType::HoldRelease, 'release');
    }

    public function void(ReleaseHoldRequestData $data): EconomyHold
    {
        return $this->voidHold($data);
    }

    public function voidHold(ReleaseHoldRequestData $data): EconomyHold
    {
        return $this->releaseOrVoid($data, EconomyHoldStatus::Cancelled, EconomyTransactionType::HoldVoid, 'void');
    }

    protected function releaseOrVoid(
        ReleaseHoldRequestData $data,
        EconomyHoldStatus $targetStatus,
        EconomyTransactionType $transactionType,
        string $hookAction,
    ): EconomyHold {
        return DB::transaction(function () use ($data, $targetStatus, $transactionType, $hookAction) {
            $hold = $this->holds->findByIdForUpdate($data->holdId);
            if ($hold === null) {
                throw new HoldNotFoundException($data->holdId);
            }

            if ($hold->hold_status !== EconomyHoldStatus::Active) {
                throw new HoldStateException();
            }

            $wallet = $this->lockWallet((int) $hold->wallet_id);

            HookManager::doAction("glitter-economy.hold.before_{$hookAction}", $hold, $wallet, $data);

            $balance = $this->decimalValue($wallet->balance);
            $lockedBalance = $this->decimalValue($wallet->locked_balance);
            $amount = $this->decimalValue($hold->amount);
            $newLockedBalance = $this->subtractDecimal($lockedBalance, $amount);

            if ($this->compareDecimal($newLockedBalance, '0.00') < 0) {
                throw new HoldStateException();
            }

            $newAvailableBalance = $this->subtractDecimal($balance, $newLockedBalance);

            $this->transactions->createLedgerEntry($this->ledgerData(
                wallet: $wallet,
                hold: $hold,
                type: $transactionType,
                amount: $amount,
                balanceAfter: $balance,
                lockedBalanceAfter: $newLockedBalance,
                availableBalanceAfter: $newAvailableBalance,
                context: $data->context,
            ));

            $updatedHold = $this->holds->updateStatus($hold, $targetStatus, [
                'released_by' => $data->releasedBy,
                'released_at' => now(),
            ]);
            $updatedWallet = $this->wallets->updateBalances($wallet, $balance, $newLockedBalance);

            HookManager::doAction("glitter-economy.hold.after_{$hookAction}", $updatedHold, $updatedWallet, $data);

            return $updatedHold;
        });
    }

    protected function lockWallet(int $walletId): EconomyWallet
    {
        $wallet = $this->wallets->findByIdForUpdate($walletId);
        if ($wallet === null) {
            throw new WalletNotFoundException($walletId);
        }

        return $wallet;
    }

    protected function normalizePositiveAmount(string $amount): string
    {
        $normalized = $this->normalizeDecimal($amount);
        if ($this->compareDecimal($normalized, '0.00') <= 0) {
            throw new InvalidEconomyAmountException();
        }

        return $normalized;
    }

    protected function assertAvailableBalance(string $availableBalance, string $amount): void
    {
        if ($this->allowsNegativeBalance()) {
            return;
        }

        if ($this->compareDecimal($availableBalance, $amount) < 0) {
            throw new InsufficientBalanceException();
        }
    }

    protected function assertNonNegativeAvailableBalance(string $availableBalance): void
    {
        if ($this->allowsNegativeBalance()) {
            return;
        }

        if ($this->compareDecimal($availableBalance, '0.00') < 0) {
            throw new InsufficientBalanceException();
        }
    }

    protected function allowsNegativeBalance(): bool
    {
        return (bool) $this->settings->getSetting('wallet.allow_negative_balance', false);
    }

    /**
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    protected function ledgerData(
        EconomyWallet $wallet,
        EconomyHold $hold,
        EconomyTransactionType $type,
        string $amount,
        string $balanceAfter,
        string $lockedBalanceAfter,
        string $availableBalanceAfter,
        array $context = [],
    ): array {
        return [
            'wallet_id' => $wallet->id,
            'transaction_type' => $type,
            'amount' => $amount,
            'balance_after' => $balanceAfter,
            'locked_balance_after' => $lockedBalanceAfter,
            'available_balance_after' => $availableBalanceAfter,
            'source_module' => $hold->source_module ?? $context['source_module'] ?? null,
            'source_type' => $hold->source_type ?? $context['source_type'] ?? null,
            'source_id' => $hold->source_id ?? $context['source_id'] ?? null,
            'correlation_id' => $hold->correlation_id ?? $context['correlation_id'] ?? null,
            'idempotency_key' => $context['idempotency_key'] ?? null,
            'description' => $context['description'] ?? null,
            'metadata' => array_merge(is_array($context['metadata'] ?? null) ? $context['metadata'] : [], ['hold_id' => $hold->id]),
            'created_by' => $context['created_by'] ?? $hold->created_by,
            'occurred_at' => $context['occurred_at'] ?? now(),
        ];
    }

    protected function decimalValue(mixed $value): string
    {
        return $this->normalizeDecimal($value);
    }

    protected function normalizeDecimal(mixed $value): string
    {
        $normalized = trim((string) $value);
        if (! preg_match('/^(-?)(\d+)(?:\.(\d{1,2}))?$/', $normalized, $matches)) {
            throw new InvalidEconomyAmountException();
        }

        $whole = ltrim($matches[2], '0');
        $whole = $whole === '' ? '0' : $whole;
        $fraction = str_pad($matches[3] ?? '', 2, '0');
        $sign = ($matches[1] ?? '') === '-' && ($whole !== '0' || $fraction !== '00') ? '-' : '';

        return $sign.$whole.'.'.$fraction;
    }

    protected function addDecimal(string $left, string $right): string
    {
        if (! function_exists('bcadd')) {
            throw new EconomyException('glitter-economy::messages.exceptions.decimal_math_unavailable');
        }

        return bcadd($left, $right, 2);
    }

    protected function subtractDecimal(string $left, string $right): string
    {
        if (! function_exists('bcsub')) {
            throw new EconomyException('glitter-economy::messages.exceptions.decimal_math_unavailable');
        }

        return bcsub($left, $right, 2);
    }

    protected function compareDecimal(string $left, string $right): int
    {
        if (! function_exists('bccomp')) {
            throw new EconomyException('glitter-economy::messages.exceptions.decimal_math_unavailable');
        }

        return bccomp($left, $right, 2);
    }
}
