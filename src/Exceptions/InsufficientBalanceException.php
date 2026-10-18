<?php

namespace Modules\Glitter\Economy\Exceptions;

class InsufficientBalanceException extends EconomyException
{
    public function __construct()
    {
        parent::__construct('glitter-economy::messages.exceptions.insufficient_balance');
    }
}
