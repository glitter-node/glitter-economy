<?php

namespace Modules\Glitter\Economy\Services;

use App\Extension\HookManager;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Glitter\Economy\Contracts\Repositories\EconomyTransactionRepositoryInterface;
use Modules\Glitter\Economy\Contracts\Repositories\EconomyWalletRepositoryInterface;
use Modules\Glitter\Economy\DTO\CreditRequestData;
use Modules\Glitter\Economy\DTO\DebitRequestData;
use Modules\Glitter\Economy\DTO\TransferRequestData;
use Modules\Glitter\Economy\Enums\EconomyTransactionType;
use Modules\Glitter\Economy\Exceptions\EconomyException;
use Modules\Glitter\Economy\Exceptions\InsufficientBalanceException;
use Modules\Glitter\Economy\Exceptions\InvalidEconomyAmountException;
use Modules\Glitter\Economy\Exceptions\WalletNotFoundException;
use Modules\Glitter\Economy\Models\EconomyTransaction;
use Modules\Glitter\Economy\Models\EconomyWallet;

class WalletService
{
    public function __construct(
        protected EconomyWalletRepositoryInterface $wallets,
        protected EconomyTransactionRepositoryInterface $transactions,
        protected EconomySettingsService $settings,
    ) {}

    public function findWalletByUserId(int $userId, string $walletType = 'default', string $currencyCode = 'POINT'): ?EconomyWallet
    {
        return $this->wallets->findByUserId($userId, $walletType, $currencyCode);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function createWalletForUser(int $userId, array $attributes = []): EconomyWallet
    {
        return $this->wallets->createForUser($userId, $attributes);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function getOrCreateWalletForUser(int $userId, array $attributes = []): EconomyWallet
    {
        return $this->wallets->getOrCreateForUser($userId, $attributes);
    }

    public function credit(CreditRequestData $data): EconomyTransaction
    {
        return DB::transaction(function () use ($data) {
            $amount = $this->normalizePositiveAmount($data->amount);
            $wallet = $this->lockWallet($data->walletId);

            HookManager::doAction('glitter-economy.wallet.before_credit', $wallet, $data);

            $newBalance = $this->addDecimal($this->decimalValue($wallet->balance), $amount);
            $lockedBalance = $this->decimalValue($wallet->locked_balance);
            $availableBalance = $this->subtractDecimal($newBalance, $lockedBalance);

            $transaction = $this->transactions->createLedgerEntry($this->ledgerData(
                wallet: $wallet,
                type: EconomyTransactionType::Credit,
                amount: $amount,
                balanceAfter: $newBalance,
                lockedBalanceAfter: $lockedBalance,
                availableBalanceAfter: $availableBalance,
                context: $data->context,
            ));

            $updatedWallet = $this->wallets->updateBalances($wallet, $newBalance, $lockedBalance);

            HookManager::doAction('glitter-economy.wallet.after_credit', $updatedWallet, $transaction, $data);

            return $transaction;
        });
    }

    public function debit(DebitRequestData $data): EconomyTransaction
    {
        return DB::transaction(function () use ($data) {
            $amount = $this->normalizePositiveAmount($data->amount);
            $wallet = $this->lockWallet($data->walletId);

            HookManager::doAction('glitter-economy.wallet.before_debit', $wallet, $data);

            $balance = $this->decimalValue($wallet->balance);
            $lockedBalance = $this->decimalValue($wallet->locked_balance);
            $availableBalance = $this->subtractDecimal($balance, $lockedBalance);
            $this->assertAvailableBalance($availableBalance, $amount);

            $newBalance = $this->subtractDecimal($balance, $amount);
            $newAvailableBalance = $this->subtractDecimal($newBalance, $lockedBalance);
            $this->assertNonNegativeAvailableBalance($newAvailableBalance);

            $transaction = $this->transactions->createLedgerEntry($this->ledgerData(
                wallet: $wallet,
                type: EconomyTransactionType::Debit,
                amount: $amount,
                balanceAfter: $newBalance,
                lockedBalanceAfter: $lockedBalance,
                availableBalanceAfter: $newAvailableBalance,
                context: $data->context,
            ));

            $updatedWallet = $this->wallets->updateBalances($wallet, $newBalance, $lockedBalance);

            HookManager::doAction('glitter-economy.wallet.after_debit', $updatedWallet, $transaction, $data);

            return $transaction;
        });
    }

    /**
     * @return array{out: EconomyTransaction, in: EconomyTransaction}
     */
    public function transfer(TransferRequestData $data): array
    {
        return DB::transaction(function () use ($data) {
            $amount = $this->normalizePositiveAmount($data->amount);
            if ($data->fromWalletId === $data->toWalletId) {
                throw new EconomyException('glitter-economy::messages.exceptions.invalid_transfer');
            }

            $lockedWallets = $this->lockWalletsInIdOrder($data->fromWalletId, $data->toWalletId);
            $source = $lockedWallets[$data->fromWalletId] ?? throw new WalletNotFoundException($data->fromWalletId);
            $target = $lockedWallets[$data->toWalletId] ?? throw new WalletNotFoundException($data->toWalletId);

            HookManager::doAction('glitter-economy.wallet.before_transfer', $source, $target, $data);

            $sourceBalance = $this->decimalValue($source->balance);
            $sourceLockedBalance = $this->decimalValue($source->locked_balance);
            $sourceAvailableBalance = $this->subtractDecimal($sourceBalance, $sourceLockedBalance);
            $this->assertAvailableBalance($sourceAvailableBalance, $amount);

            $sourceNewBalance = $this->subtractDecimal($sourceBalance, $amount);
            $sourceNewAvailableBalance = $this->subtractDecimal($sourceNewBalance, $sourceLockedBalance);
            $this->assertNonNegativeAvailableBalance($sourceNewAvailableBalance);

            $targetBalance = $this->decimalValue($target->balance);
            $targetLockedBalance = $this->decimalValue($target->locked_balance);
            $targetNewBalance = $this->addDecimal($targetBalance, $amount);
            $targetNewAvailableBalance = $this->subtractDecimal($targetNewBalance, $targetLockedBalance);

            $context = $data->context;
            $context['correlation_id'] = (string) ($context['correlation_id'] ?? Str::uuid());
            $outContext = $this->transferLedgerContext($context, 'out');
            $inContext = $this->transferLedgerContext($context, 'in');

            $out = $this->transactions->createLedgerEntry($this->ledgerData(
                wallet: $source,
                type: EconomyTransactionType::TransferOut,
                amount: $amount,
                balanceAfter: $sourceNewBalance,
                lockedBalanceAfter: $sourceLockedBalance,
                availableBalanceAfter: $sourceNewAvailableBalance,
                context: $outContext,
                relatedWallet: $target,
            ));

            $in = $this->transactions->createLedgerEntry($this->ledgerData(
                wallet: $target,
                type: EconomyTransactionType::TransferIn,
                amount: $amount,
                balanceAfter: $targetNewBalance,
                lockedBalanceAfter: $targetLockedBalance,
                availableBalanceAfter: $targetNewAvailableBalance,
                context: $inContext,
                relatedWallet: $source,
            ));

            $updatedSource = $this->wallets->updateBalances($source, $sourceNewBalance, $sourceLockedBalance);
            $updatedTarget = $this->wallets->updateBalances($target, $targetNewBalance, $targetLockedBalance);

            HookManager::doAction('glitter-economy.wallet.after_transfer', $updatedSource, $updatedTarget, $out, $in, $data);

            return ['out' => $out, 'in' => $in];
        });
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Collection<int, EconomyTransaction>
     */
    public function listTransactions(array $filters = []): Collection
    {
        return $this->transactions->listTransactions($filters);
    }

    /**
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    protected function transferLedgerContext(array $context, string $direction): array
    {
        if (isset($context['idempotency_key']) && $context['idempotency_key'] !== null && $context['idempotency_key'] !== '') {
            $context['idempotency_key'] = $context['idempotency_key'].'-'.$direction;
        }

        return $context;
    }

    protected function lockWallet(int $walletId): EconomyWallet
    {
        $wallet = $this->wallets->findByIdForUpdate($walletId);
        if ($wallet === null) {
            throw new WalletNotFoundException($walletId);
        }

        return $wallet;
    }

    /**
     * @return array<int, EconomyWallet>
     */
    protected function lockWalletsInIdOrder(int ...$walletIds): array
    {
        $locked = [];
        $ids = array_values(array_unique($walletIds));
        sort($ids, SORT_NUMERIC);

        foreach ($ids as $walletId) {
            $locked[(int) $walletId] = $this->lockWallet((int) $walletId);
        }

        return $locked;
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
        EconomyTransactionType $type,
        string $amount,
        string $balanceAfter,
        string $lockedBalanceAfter,
        string $availableBalanceAfter,
        array $context = [],
        ?EconomyWallet $relatedWallet = null,
    ): array {
        return [
            'wallet_id' => $wallet->id,
            'related_wallet_id' => $relatedWallet?->id,
            'transaction_type' => $type,
            'amount' => $amount,
            'balance_after' => $balanceAfter,
            'locked_balance_after' => $lockedBalanceAfter,
            'available_balance_after' => $availableBalanceAfter,
            'source_module' => $context['source_module'] ?? null,
            'source_type' => $context['source_type'] ?? null,
            'source_id' => $context['source_id'] ?? null,
            'correlation_id' => $context['correlation_id'] ?? null,
            'idempotency_key' => $context['idempotency_key'] ?? null,
            'description' => $context['description'] ?? null,
            'metadata' => is_array($context['metadata'] ?? null) ? $context['metadata'] : [],
            'created_by' => $context['created_by'] ?? null,
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
