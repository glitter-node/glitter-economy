<?php

namespace Modules\Glitter\Economy\Providers;

use App\Extension\BaseModuleServiceProvider;
use Modules\Glitter\Economy\Console\Commands\TestEconomyFlowCommand;
use Modules\Glitter\Economy\Console\Commands\VerifyAdminAdjustmentsCommand;
use Modules\Glitter\Economy\Console\Commands\VerifyAdminWalletLayoutCommand;
use Modules\Glitter\Economy\Contracts\Repositories\EconomyHoldRepositoryInterface;
use Modules\Glitter\Economy\Contracts\Repositories\EconomyTransactionRepositoryInterface;
use Modules\Glitter\Economy\Contracts\Repositories\EconomyWalletRepositoryInterface;
use Modules\Glitter\Economy\Repositories\EconomyHoldRepository;
use Modules\Glitter\Economy\Repositories\EconomyTransactionRepository;
use Modules\Glitter\Economy\Repositories\EconomyWalletRepository;

class EconomyServiceProvider extends BaseModuleServiceProvider
{
    protected string $moduleIdentifier = 'glitter-economy';

    /**
     * @var array<class-string, class-string>
     */
    protected array $repositories = [
        EconomyWalletRepositoryInterface::class => EconomyWalletRepository::class,
        EconomyTransactionRepositoryInterface::class => EconomyTransactionRepository::class,
        EconomyHoldRepositoryInterface::class => EconomyHoldRepository::class,
    ];

    /** @var array<int, class-string> */
    protected array $commands = [
        TestEconomyFlowCommand::class,
        VerifyAdminAdjustmentsCommand::class,
        VerifyAdminWalletLayoutCommand::class,
    ];

    public function boot(): void
    {
        parent::boot();

        if ($this->app->runningInConsole()) {
            $this->commands($this->commands);
        }
    }
}
