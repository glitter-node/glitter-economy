<?php

namespace Modules\Glitter\Economy\Exceptions;

class HoldStateException extends EconomyException
{
    public function __construct()
    {
        parent::__construct('glitter-economy::messages.exceptions.invalid_hold_state');
    }
}
