<?php

namespace Modules\Glitter\Economy\Exceptions;

class InvalidEconomyAmountException extends EconomyException
{
    public function __construct()
    {
        parent::__construct('glitter-economy::messages.exceptions.invalid_amount');
    }
}
