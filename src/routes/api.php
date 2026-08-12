<?php

use Illuminate\Support\Facades\Route;
use Modules\Glitter\Economy\Http\Controllers\Api\Admin\EconomyWalletController as AdminEconomyWalletController;
use Modules\Glitter\Economy\Http\Controllers\Api\User\EconomyWalletController as UserEconomyWalletController;

Route::prefix('wallet')->middleware(['auth:sanctum', 'throttle:600,1'])->name('wallet.')->group(function () {
    Route::get('/', [UserEconomyWalletController::class, 'show'])
        ->middleware('permission:user,glitter-economy.wallet.view')
        ->name('show');

    Route::get('/transactions', [UserEconomyWalletController::class, 'transactions'])
        ->middleware('permission:user,glitter-economy.wallet.view')
        ->name('transactions');

    Route::post('/transfer', [UserEconomyWalletController::class, 'transfer'])
        ->middleware('permission:user,glitter-economy.wallet.transfer')
        ->name('transfer');
});

Route::prefix('user')->middleware(['auth:sanctum', 'throttle:600,1'])->name('user.')->group(function () {
    Route::get('/wallet', [UserEconomyWalletController::class, 'show'])
        ->middleware('permission:user,glitter-economy.wallet.view')
        ->name('wallet.show');

    Route::get('/transactions', [UserEconomyWalletController::class, 'transactions'])
        ->middleware('permission:user,glitter-economy.wallet.view')
        ->name('transactions.index');
});

Route::prefix('admin')->middleware(['auth:sanctum', 'admin', 'throttle:600,1'])->name('admin.')->group(function () {
    Route::get('/wallets', [AdminEconomyWalletController::class, 'index'])
        ->middleware('permission:admin,glitter-economy.admin.access')
        ->name('wallets.index');

    Route::get('/transactions', [AdminEconomyWalletController::class, 'transactions'])
        ->middleware('permission:admin,glitter-economy.transactions.view')
        ->name('transactions.index');

    Route::get('/wallets/users/{userId}', [AdminEconomyWalletController::class, 'show'])
        ->whereNumber('userId')
        ->middleware('permission:admin,glitter-economy.admin.access')
        ->name('wallets.show');

    Route::post('/wallets/credit', [AdminEconomyWalletController::class, 'creditByUser'])
        ->middleware('permission:admin,glitter-economy.adjust.access')
        ->name('wallets.credit-by-user');

    Route::post('/wallets/debit', [AdminEconomyWalletController::class, 'debitByUser'])
        ->middleware('permission:admin,glitter-economy.adjust.access')
        ->name('wallets.debit-by-user');

    Route::post('/wallets/{walletId}/credit', [AdminEconomyWalletController::class, 'credit'])
        ->whereNumber('walletId')
        ->middleware('permission:admin,glitter-economy.adjust.access')
        ->name('wallets.credit');

    Route::post('/wallets/{walletId}/debit', [AdminEconomyWalletController::class, 'debit'])
        ->whereNumber('walletId')
        ->middleware('permission:admin,glitter-economy.adjust.access')
        ->name('wallets.debit');

    Route::post('/wallets/{walletId}/holds', [AdminEconomyWalletController::class, 'createHold'])
        ->whereNumber('walletId')
        ->middleware('permission:admin,glitter-economy.adjust.access')
        ->name('wallets.holds.create');

    Route::post('/holds/{holdId}/release', [AdminEconomyWalletController::class, 'releaseHold'])
        ->whereNumber('holdId')
        ->middleware('permission:admin,glitter-economy.adjust.access')
        ->name('holds.release');

    Route::post('/holds/{holdId}/void', [AdminEconomyWalletController::class, 'voidHold'])
        ->whereNumber('holdId')
        ->middleware('permission:admin,glitter-economy.adjust.access')
        ->name('holds.void');
});
