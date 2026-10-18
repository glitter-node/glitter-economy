<?php

namespace Modules\Glitter\Economy\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Modules\Glitter\Economy\DTO\CreditRequestData;
use Modules\Glitter\Economy\DTO\HoldRequestData;
use Modules\Glitter\Economy\DTO\ReleaseHoldRequestData;
use Modules\Glitter\Economy\DTO\TransferRequestData;
use Modules\Glitter\Economy\Enums\EconomyHoldStatus;
use Modules\Glitter\Economy\Enums\EconomyTransactionType;
use Modules\Glitter\Economy\Models\EconomyTransaction;
use Modules\Glitter\Economy\Services\EconomyService;
use RuntimeException;
use Throwable;

class TestEconomyFlowCommand extends Command
{
    protected $signature = 'glitter-economy:test {--persist : Commit verification data instead of rolling it back}';
    protected $description = 'Run the post-install Glitter Economy verification flow';
    private string $currentStep = 'initialize';

    public function __construct(private readonly EconomyService $economy)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $persist = (bool) $this->option('persist');
        $runId = (string) Str::uuid();
        DB::beginTransaction();

        try {
            $userA = $this->step('locate/create Economy Test A', fn () => $this->testUser('Economy Test A', 'economy-test-a@glitter-economy.invalid'));
            $userB = $this->step('locate/create Economy Test B', fn () => $this->testUser('Economy Test B', 'economy-test-b@glitter-economy.invalid'));
            $walletA = $this->step('locate/create wallet A', fn () => $this->economy->getOrCreateWalletForUser($userA->id));
            $walletB = $this->step('locate/create wallet B', fn () => $this->economy->getOrCreateWalletForUser($userB->id));

            $aBalance = (string) $walletA->balance;
            $aLocked = (string) $walletA->locked_balance;
            $bBalance = (string) $walletB->balance;
            $bLocked = (string) $walletB->locked_balance;

            $credit = $this->step('credit A 10000', fn () => $this->economy->credit(new CreditRequestData($walletA->id, '10000.00', $this->verificationContext($runId, 'credit'))));
            $this->verifyWallet('verify A after credit', $userA->id, $this->add($aBalance, '10000.00'), $aLocked);

            $transfer = $this->step('transfer A to B 3000', fn () => $this->economy->transfer(new TransferRequestData($walletA->id, $walletB->id, '3000.00', $this->verificationContext($runId, 'transfer'))));
            $this->verifyWallet('verify A after transfer', $userA->id, $this->add($aBalance, '7000.00'), $aLocked);
            $this->verifyWallet('verify B after transfer', $userB->id, $this->add($bBalance, '3000.00'), $bLocked);

            $releaseCorrelation = $runId.'-release';
            $releaseHold = $this->step('create hold on A 2000 for release', fn () => $this->economy->createHold($this->holdData($walletA->id, $runId, $releaseCorrelation, 'release')));
            $this->verifyWallet('verify A after first hold', $userA->id, $this->add($aBalance, '7000.00'), $this->add($aLocked, '2000.00'));
            $released = $this->step('release hold', fn () => $this->economy->releaseHold(new ReleaseHoldRequestData($releaseHold->id, $userA->id, $this->verificationContext($runId, 'release'))));
            $this->step('verify released hold status', fn () => $this->assertSame(EconomyHoldStatus::Released, $released->fresh()?->hold_status));
            $this->verifyWallet('verify A after release', $userA->id, $this->add($aBalance, '7000.00'), $aLocked);

            $voidCorrelation = $runId.'-void';
            $voidHold = $this->step('create hold on A 2000 for void', fn () => $this->economy->createHold($this->holdData($walletA->id, $runId, $voidCorrelation, 'void')));
            $this->verifyWallet('verify A after second hold', $userA->id, $this->add($aBalance, '7000.00'), $this->add($aLocked, '2000.00'));
            $voided = $this->step('void hold', fn () => $this->economy->voidHold(new ReleaseHoldRequestData($voidHold->id, $userA->id, $this->verificationContext($runId, 'void'))));
            $this->step('verify voided hold status', fn () => $this->assertSame(EconomyHoldStatus::Cancelled, $voided->fresh()?->hold_status));
            $this->verifyWallet('verify final wallet A', $userA->id, $this->add($aBalance, '7000.00'), $aLocked);
            $this->verifyWallet('verify final wallet B', $userB->id, $this->add($bBalance, '3000.00'), $bLocked);

            $this->step('verify ledger rows', function () use ($credit, $transfer, $releaseCorrelation, $voidCorrelation): void {
                $this->assertLedgerType($credit->id, EconomyTransactionType::Credit);
                $this->assertLedgerType($transfer['out']->id, EconomyTransactionType::TransferOut);
                $this->assertLedgerType($transfer['in']->id, EconomyTransactionType::TransferIn);
                $this->assertCorrelationTypes($releaseCorrelation, [EconomyTransactionType::Hold, EconomyTransactionType::HoldRelease]);
                $this->assertCorrelationTypes($voidCorrelation, [EconomyTransactionType::Hold, EconomyTransactionType::HoldVoid]);
            });

            $persist ? DB::commit() : DB::rollBack();
            $this->info('PASS '.($persist ? 'persist' : 'rollback').' verification data');
            return self::SUCCESS;
        } catch (Throwable $exception) {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }
            $this->error("FAIL {$this->currentStep}: {$exception->getMessage()}");
            return self::FAILURE;
        }
    }

    private function testUser(string $name, string $email): User
    {
        return User::query()->firstOrCreate(['email' => $email], [
            'name' => $name,
            'password' => Hash::make(Str::random(64)),
            'email_verified_at' => now(),
            'status' => 'active',
            'admin_memo' => 'Created by glitter-economy:test --persist',
        ]);
    }

    private function holdData(int $walletId, string $runId, string $correlationId, string $action): HoldRequestData
    {
        return new HoldRequestData($walletId, '2000.00', "Glitter Economy verification {$action}", 'glitter-economy', 'verification', $runId, $correlationId);
    }

    private function step(string $name, callable $callback): mixed
    {
        $this->currentStep = $name;
        $result = $callback();
        $this->info("PASS {$name}");
        return $result;
    }

    private function verifyWallet(string $step, int $userId, string $balance, string $locked): void
    {
        $this->step($step, function () use ($userId, $balance, $locked): void {
            $wallet = $this->economy->findWalletByUserId($userId) ?? throw new RuntimeException("Wallet for user {$userId} was not found.");
            $this->assertDecimal($balance, (string) $wallet->balance);
            $this->assertDecimal($locked, (string) $wallet->locked_balance);
            $this->assertDecimal($this->subtract($balance, $locked), (string) $wallet->available_balance);
        });
    }

    private function assertLedgerType(int $id, EconomyTransactionType $type): void
    {
        $this->assertSame($type, EconomyTransaction::query()->find($id)?->transaction_type);
    }

    private function assertCorrelationTypes(string $correlationId, array $types): void
    {
        $actual = EconomyTransaction::query()->where('correlation_id', $correlationId)->orderBy('id')->get()->pluck('transaction_type')->all();
        $this->assertSame($types, $actual);
    }

    private function assertDecimal(string $expected, string $actual): void
    {
        if (bccomp($expected, $actual, 2) !== 0) {
            throw new RuntimeException("Expected {$expected}, got {$actual}.");
        }
    }

    private function assertSame(mixed $expected, mixed $actual): void
    {
        if ($expected !== $actual) {
            $expectedValue = $expected instanceof \BackedEnum ? $expected->value : var_export($expected, true);
            $actualValue = $actual instanceof \BackedEnum ? $actual->value : var_export($actual, true);
            throw new RuntimeException("Expected {$expectedValue}, got {$actualValue}.");
        }
    }

    private function verificationContext(string $runId, string $operation): array
    {
        return [
            'source_module' => 'glitter-economy',
            'source_type' => 'verification',
            'source_id' => $runId,
            'correlation_id' => "{$runId}-{$operation}",
            'idempotency_key' => "{$runId}-{$operation}",
            'description' => "glitter-economy:test {$operation}",
        ];
    }

    private function add(string $left, string $right): string { return bcadd($left, $right, 2); }
    private function subtract(string $left, string $right): string { return bcsub($left, $right, 2); }
}
