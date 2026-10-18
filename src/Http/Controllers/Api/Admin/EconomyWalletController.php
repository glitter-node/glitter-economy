<?php

namespace Modules\Glitter\Economy\Http\Controllers\Api\Admin;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Api\Base\AdminBaseController;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Glitter\Economy\DTO\CreditRequestData;
use Modules\Glitter\Economy\DTO\DebitRequestData;
use Modules\Glitter\Economy\DTO\HoldRequestData;
use Modules\Glitter\Economy\DTO\ReleaseHoldRequestData;
use Modules\Glitter\Economy\Exceptions\EconomyException;
use Modules\Glitter\Economy\Http\Requests\AdminCreditWalletRequest;
use Modules\Glitter\Economy\Http\Requests\AdminDebitWalletRequest;
use Modules\Glitter\Economy\Http\Requests\CreateHoldRequest;
use Modules\Glitter\Economy\Http\Requests\CreditWalletRequest;
use Modules\Glitter\Economy\Http\Requests\DebitWalletRequest;
use Modules\Glitter\Economy\Http\Requests\ReleaseHoldRequest;
use Modules\Glitter\Economy\Contracts\Repositories\EconomyTransactionRepositoryInterface;
use Modules\Glitter\Economy\Contracts\Repositories\EconomyWalletRepositoryInterface;
use Modules\Glitter\Economy\Http\Resources\EconomyHoldResource;
use Modules\Glitter\Economy\Http\Resources\EconomyTransactionCollection;
use Modules\Glitter\Economy\Http\Resources\EconomyTransactionResource;
use Modules\Glitter\Economy\Http\Resources\EconomyWalletCollection;
use Modules\Glitter\Economy\Http\Resources\EconomyWalletResource;
use Modules\Glitter\Economy\Services\EconomyService;
use Throwable;

class EconomyWalletController extends AdminBaseController
{
    public function __construct(
        private EconomyService $economyService,
        private EconomyWalletRepositoryInterface $walletRepository,
        private EconomyTransactionRepositoryInterface $transactionRepository,
    ) {
        parent::__construct();
    }

    public function index(Request $request): JsonResponse
    {
        $filters = $request->only([
            'search',
            'user_id',
            'wallet_type',
            'currency_code',
            'status',
        ]);
        $perPage = (int) $request->integer('per_page', 20);

        $wallets = $this->walletRepository->paginateForAdmin($filters, $perPage);
        $summary = $this->walletRepository->summarizeForAdmin($filters);

        return ResponseHelper::moduleSuccessWithResource(
            'glitter-economy',
            'messages.api.wallets_fetched',
            new EconomyWalletCollection($wallets, $summary),
        );
    }

    public function transactions(Request $request): JsonResponse
    {
        $filters = $request->only([
            'wallet_id',
            'user_id',
            'type',
            'transaction_type',
            'reference_type',
            'reference_id',
            'source_module',
            'correlation_id',
            'date_from',
            'date_to',
        ]);
        $perPage = (int) $request->integer('per_page', 20);

        return ResponseHelper::moduleSuccessWithResource(
            'glitter-economy',
            'messages.api.transactions_fetched',
            new EconomyTransactionCollection($this->transactionRepository->paginateForAdmin($filters, $perPage)),
        );
    }

    public function show(int $userId): JsonResponse
    {
        $wallet = $this->economyService->findWalletByUserId($userId);

        if ($wallet === null) {
            return $this->moduleError('messages.api.wallet_not_found', 404);
        }

        return ResponseHelper::moduleSuccessWithResource(
            'glitter-economy',
            'messages.api.wallet_fetched',
            new EconomyWalletResource($wallet),
        );
    }

    public function credit(CreditWalletRequest $request, int $walletId): JsonResponse
    {
        try {
            $validated = $request->validated();
            $transaction = $this->economyService->credit(new CreditRequestData(
                walletId: $walletId,
                amount: (string) $validated['amount'],
                context: $this->withActor($validated['context'] ?? []),
            ));

            return ResponseHelper::moduleSuccessWithResource(
                'glitter-economy',
                'messages.api.wallet_credited',
                new EconomyTransactionResource($transaction),
                201,
            );
        } catch (EconomyException $exception) {
            return $this->moduleError('messages.api.mutation_rejected', 422, $exception);
        } catch (Throwable $exception) {
            return $this->moduleError('messages.api.mutation_failed', 500, $exception);
        }
    }

    public function debit(DebitWalletRequest $request, int $walletId): JsonResponse
    {
        try {
            $validated = $request->validated();
            $transaction = $this->economyService->debit(new DebitRequestData(
                walletId: $walletId,
                amount: (string) $validated['amount'],
                context: $this->withActor($validated['context'] ?? []),
            ));

            return ResponseHelper::moduleSuccessWithResource(
                'glitter-economy',
                'messages.api.wallet_debited',
                new EconomyTransactionResource($transaction),
                201,
            );
        } catch (EconomyException $exception) {
            return $this->moduleError('messages.api.mutation_rejected', 422, $exception);
        } catch (Throwable $exception) {
            return $this->moduleError('messages.api.mutation_failed', 500, $exception);
        }
    }

    public function creditByUser(AdminCreditWalletRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $wallet = $this->economyService->getOrCreateWalletForUser((int) $validated['user_id']);

        try {
            $transaction = $this->economyService->credit(new CreditRequestData(
                walletId: $wallet->id,
                amount: (string) $validated['amount'],
                context: $this->manualAdjustmentContext($validated['memo'] ?? null),
            ));

            return ResponseHelper::moduleSuccessWithResource(
                'glitter-economy',
                'messages.api.wallet_credited',
                new EconomyTransactionResource($transaction),
                201,
            );
        } catch (EconomyException $exception) {
            return $this->moduleError('messages.api.mutation_rejected', 422, $exception);
        } catch (Throwable $exception) {
            return $this->moduleError('messages.api.mutation_failed', 500, $exception);
        }
    }

    public function debitByUser(AdminDebitWalletRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $wallet = $this->economyService->findWalletByUserId((int) $validated['user_id']);

        if ($wallet === null) {
            return $this->moduleError('messages.api.wallet_not_found', 404);
        }

        try {
            $transaction = $this->economyService->debit(new DebitRequestData(
                walletId: $wallet->id,
                amount: (string) $validated['amount'],
                context: $this->manualAdjustmentContext($validated['memo'] ?? null),
            ));

            return ResponseHelper::moduleSuccessWithResource(
                'glitter-economy',
                'messages.api.wallet_debited',
                new EconomyTransactionResource($transaction),
                201,
            );
        } catch (EconomyException $exception) {
            return $this->moduleError('messages.api.mutation_rejected', 422, $exception);
        } catch (Throwable $exception) {
            return $this->moduleError('messages.api.mutation_failed', 500, $exception);
        }
    }

    public function createHold(CreateHoldRequest $request, int $walletId): JsonResponse
    {
        try {
            $validated = $request->validated();
            $metadata = $this->withActor($validated['metadata'] ?? []);
            $hold = $this->economyService->createHold(new HoldRequestData(
                walletId: $walletId,
                amount: (string) $validated['amount'],
                reason: $validated['reason'] ?? null,
                sourceModule: $validated['source_module'] ?? null,
                sourceType: $validated['source_type'] ?? null,
                sourceId: $validated['source_id'] ?? null,
                correlationId: $validated['correlation_id'] ?? null,
                expiresAt: isset($validated['expires_at']) ? CarbonImmutable::parse($validated['expires_at']) : null,
                metadata: $metadata,
            ));

            return ResponseHelper::moduleSuccessWithResource(
                'glitter-economy',
                'messages.api.hold_created',
                new EconomyHoldResource($hold),
                201,
            );
        } catch (EconomyException $exception) {
            return $this->moduleError('messages.api.mutation_rejected', 422, $exception);
        } catch (Throwable $exception) {
            return $this->moduleError('messages.api.mutation_failed', 500, $exception);
        }
    }

    public function releaseHold(ReleaseHoldRequest $request, int $holdId): JsonResponse
    {
        return $this->changeHoldState($request, $holdId, false);
    }

    public function voidHold(ReleaseHoldRequest $request, int $holdId): JsonResponse
    {
        return $this->changeHoldState($request, $holdId, true);
    }

    private function changeHoldState(ReleaseHoldRequest $request, int $holdId, bool $void): JsonResponse
    {
        try {
            $validated = $request->validated();
            $data = new ReleaseHoldRequestData(
                holdId: $holdId,
                releasedBy: $this->getCurrentAdmin()?->id,
                context: $this->withActor($validated['context'] ?? []),
            );
            $hold = $void
                ? $this->economyService->voidHold($data)
                : $this->economyService->releaseHold($data);

            return ResponseHelper::moduleSuccessWithResource(
                'glitter-economy',
                $void ? 'messages.api.hold_voided' : 'messages.api.hold_released',
                new EconomyHoldResource($hold),
            );
        } catch (EconomyException $exception) {
            return $this->moduleError('messages.api.mutation_rejected', 422, $exception);
        } catch (Throwable $exception) {
            return $this->moduleError('messages.api.mutation_failed', 500, $exception);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function manualAdjustmentContext(?string $memo): array
    {
        return $this->withActor([
            'source_module' => 'glitter-economy',
            'source_type' => 'admin_manual_adjustment',
            'description' => $memo,
        ]);
    }

    /**
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    private function withActor(array $context): array
    {
        return array_merge($context, ['created_by' => $this->getCurrentAdmin()?->id]);
    }

    private function moduleError(string $messageKey, int $statusCode, ?Throwable $exception = null): JsonResponse
    {
        return ResponseHelper::moduleError('glitter-economy', $messageKey, $statusCode, $exception);
    }
}
