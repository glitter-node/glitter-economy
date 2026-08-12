<?php

namespace Modules\Glitter\Economy\Exceptions;

class EconomyTableUnavailableException extends EconomyException
{
    public function __construct(string $table)
    {
        parent::__construct('glitter-economy::messages.exceptions.table_unavailable', [
            'table' => $table,
        ]);
    }
}
