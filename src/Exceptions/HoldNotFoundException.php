<?php

namespace Modules\Glitter\Economy\Exceptions;

class HoldNotFoundException extends EconomyException
{
    public function __construct(int|string|null $holdId = null)
    {
        parent::__construct('glitter-economy::messages.exceptions.hold_not_found', [
            'hold_id' => $holdId ?? '-',
        ]);
    }
}
