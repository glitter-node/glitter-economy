<?php

namespace Modules\Glitter\Economy\Http\Controllers\Api\User;

use App\Helpers\ResponseHelper;
use App\Http\Controllers\Api\Base\AuthBaseController;
use Illuminate\Http\JsonResponse;
use Modules\Glitter\Economy\DTO\TransferRequestData;
use Modules\Glitter\Economy\Exceptions\EconomyException;
use Modules\Glitter\Economy\Http\Requests\TransferWalletRequest;
use Modules\Glitter\Economy\Http\Resources\EconomyTransactionResource;
use Modules\Glitter\Economy\Http\Resources\EconomyWalletResource;
use Modules\Glitter\Economy\Models\EconomyWallet;
use Modules\Glitter\Economy\Services\EconomyService;
use Modules\Glitter\Economy\Services\EconomySettingsService;
use Throwable;

class EconomyWalletController extends AuthBaseController
{
    public function __construct(
        private EconomyService $economyService,
        private EconomySettingsService $settings,
    ) {
        parent::__construct();
    }

    public function show(): JsonResponse
    {
        $wallet = $this->economyService->getOrCreateWalletForUser((int) $this->getCurrentUser()?->id);

        return ResponseHelper::moduleSuccessWithResource(
            'glitter-economy',
            'messages.api.wallet_fetched',
            new EconomyWalletResource($wallet),
        );
    }

    public function transactions(): JsonResponse
    {
        $transactions = $this->economyService->listTransactions([
            'user_id' => $this->getCurrentUser()?->id,
        ]);

        return ResponseHelper::moduleSuccessWithResource(
            'glitter-economy',
            'messages.api.transactions_fetched',
            EconomyTransactionResource::collection($transactions),
        );
    }

    public function transfer(TransferWalletRequest $request): JsonResponse
    {
        if (! (bool) $this->settings->getSetting('transfer.enabled', false)) {
            return $this->moduleError('messages.api.transfer_disabled', 403);
        }

        $wallet = $this->currentWallet();
        if ($wallet === null) {
            return $this->moduleError('messages.api.wallet_not_found', 404);
        }

        try {
            $validated = $request->validated();
            $context = array_merge($validated['context'] ?? [], [
                'created_by' => $this->getCurrentUser()?->id,
            ]);
            $transactions = $this->economyService->transfer(new TransferRequestData(
                fromWalletId: (int) $wallet->id,
                toWalletId: (int) $validated['to_wallet_id'],
                amount: (string) $validated['amount'],
                context: $context,
            ));

            return ResponseHelper::moduleSuccessWithResource(
                'glitter-economy',
                'messages.api.wallet_transferred',
                EconomyTransactionResource::collection(collect($transactions)),
                201,
            );
        } catch (EconomyException $exception) {
            return $this->moduleError('messages.api.mutation_rejected', 422, $exception);
        } catch (Throwable $exception) {
            return $this->moduleError('messages.api.mutation_failed', 500, $exception);
        }
    }

    private function currentWallet(): ?EconomyWallet
    {
        return $this->economyService->findWalletByUserId((int) $this->getCurrentUser()?->id);
    }

    private function moduleError(string $messageKey, int $statusCode, ?Throwable $exception = null): JsonResponse
    {
        return ResponseHelper::moduleError('glitter-economy', $messageKey, $statusCode, $exception);
    }
}
