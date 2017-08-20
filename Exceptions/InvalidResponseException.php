<?php

namespace Timiki\RpcClient\Exceptions;

use RuntimeException;

/**
 * Invalid RPC response
 */
class InvalidResponseException extends RuntimeException
{
    /**
     * InvalidResponse constructor.
     *
     * @param string $message
     */
    public function __construct($message = 'Invalid response from RPC server')
    {
        parent::__construct($message);
    }
}

