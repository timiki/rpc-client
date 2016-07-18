<?php

namespace Timiki\RpcClient\Exceptions;

use RuntimeException;

/**
 * Connection exception
 */
class ConnectionException extends RuntimeException
{
    /**
     * ConnectionException constructor.
     *
     * @param string $message
     */
    public function __construct($message = 'Failed to connect to RPC server')
    {
        parent::__construct($message);
    }
}

