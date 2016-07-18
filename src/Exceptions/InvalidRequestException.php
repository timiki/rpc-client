<?php

namespace Timiki\RpcClient\Exceptions;

use RuntimeException;

/**
 * Invalid RPC response
 */
class InvalidRequestException extends RuntimeException
{
    /**
     * InvalidResponse constructor.
     *
     * @param string $message
     */
    public function __construct($message = 'Invalid RPC request')
    {
        parent::__construct($message);
    }
}

