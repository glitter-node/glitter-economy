<?php

namespace Modules\Glitter\Economy\Exceptions;

class WalletNotFoundException extends EconomyException
{
    public function __construct(int|string|null $walletId = null)
    {
        parent::__construct('glitter-economy::messages.exceptions.wallet_not_found', [
            'wallet_id' => $walletId ?? '-',
        ]);
    }
}
