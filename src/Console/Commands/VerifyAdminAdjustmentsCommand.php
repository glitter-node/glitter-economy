<?php

namespace Modules\Glitter\Economy\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Modules\Glitter\Economy\DTO\DebitRequestData;
use Modules\Glitter\Economy\Exceptions\InsufficientBalanceException;
use Modules\Glitter\Economy\Http\Requests\AdminCreditWalletRequest;
use Modules\Glitter\Economy\Http\Requests\AdminDebitWalletRequest;
use Modules\Glitter\Economy\Models\EconomyTransaction;
use Modules\Glitter\Economy\Models\EconomyWallet;
use Modules\Glitter\Economy\Services\EconomyService;
use RuntimeException;
use Throwable;

class VerifyAdminAdjustmentsCommand extends Command
{
    protected $signature = 'glitter-economy:verify-adjustments';

    protected $description = 'Verify rejected admin credit and debit requests without persistent ledger writes';

    public function __construct(private readonly EconomyService $economy)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $wallet = EconomyWallet::query()->latest('id')->first();
        if ($wallet === null) {
            $this->error('FAIL no economy wallet is available for adjustment verification');

            return self::FAILURE;
        }

        $before = $this->snapshot($wallet->id);
        $result = self::SUCCESS;
        DB::beginTransaction();

        try {
            $this->assertRejectedByValidation(AdminCreditWalletRequest::class, $wallet->user_id, 'credit amount=0');
            $this->assertSnapshot($before, $wallet->id, 'credit amount=0 creates no transaction');

            $this->assertRejectedByValidation(AdminDebitWalletRequest::class, $wallet->user_id, 'debit amount=0');
            $this->assertSnapshot($before, $wallet->id, 'debit amount=0 creates no transaction');

            $available = bcsub((string) $wallet->balance, (string) $wallet->locked_balance, 2);
            $excessAmount = bcadd($available, '1.00', 2);

            try {
                $this->economy->debit(new DebitRequestData($wallet->id, $excessAmount));
                throw new RuntimeException('debit greater than available balance was not rejected');
            } catch (InsufficientBalanceException) {
                $this->info('PASS debit greater than available balance is rejected');
            }

            $this->assertSnapshot($before, $wallet->id, 'rejected excess debit creates no transaction');
        } catch (Throwable $exception) {
            $this->error("FAIL {$exception->getMessage()}");
            $result = self::FAILURE;
        } finally {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }
        }

        try {
            $this->assertSnapshot($before, $wallet->id, 'rollback preserves persistent ledger and wallet');
        } catch (Throwable $exception) {
            $this->error("FAIL {$exception->getMessage()}");
            $result = self::FAILURE;
        }

        return $result;
    }

    /** @param class-string<AdminCreditWalletRequest|AdminDebitWalletRequest> $requestClass */
    private function assertRejectedByValidation(string $requestClass, int $userId, string $step): void
    {
        $request = new $requestClass();
        $validator = Validator::make([
            'user_id' => $userId,
            'amount' => '0',
        ], $request->rules());

        if (! $validator->fails() || ! $validator->errors()->has('amount')) {
            throw new RuntimeException("{$step} was not rejected by amount validation");
        }

        $this->info("PASS {$step} is rejected by validation");
    }

    /** @return array{transactions: int, balance: string, locked_balance: string, available_balance: string} */
    private function snapshot(int $walletId): array
    {
        $wallet = EconomyWallet::query()->find($walletId) ?? throw new RuntimeException('verification wallet was not found');

        return [
            'transactions' => EconomyTransaction::query()->count(),
            'balance' => (string) $wallet->balance,
            'locked_balance' => (string) $wallet->locked_balance,
            'available_balance' => (string) $wallet->available_balance,
        ];
    }

    /** @param array{transactions: int, balance: string, locked_balance: string, available_balance: string} $expected */
    private function assertSnapshot(array $expected, int $walletId, string $step): void
    {
        if ($expected !== $this->snapshot($walletId)) {
            throw new RuntimeException("{$step}: persistent economy state changed");
        }

        $this->info("PASS {$step}");
    }
}
