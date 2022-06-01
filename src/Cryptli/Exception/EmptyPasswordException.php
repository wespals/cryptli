<?php

namespace Cryptli\Exception;

use Exception;
use Throwable;

class EmptyPasswordException extends Exception
{
    /**
     * {@inheritdoc}
     */
    public function __construct($message = 'The password cannot be empty', $code = 0, Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
