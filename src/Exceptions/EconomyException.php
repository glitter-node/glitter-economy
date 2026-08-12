<?php

namespace Modules\Glitter\Economy\Exceptions;

use RuntimeException;
use Throwable;

class EconomyException extends RuntimeException
{
    /**
     * @param  array<string, mixed>  $replace
     */
    public function __construct(
        string $translationKey = 'glitter-economy::messages.exceptions.generic',
        array $replace = [],
        int $code = 0,
        ?Throwable $previous = null,
    ) {
        parent::__construct(__($translationKey, $replace), $code, $previous);
    }
}
