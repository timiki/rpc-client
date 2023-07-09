<?php

declare(strict_types=1);

namespace Timiki\RpcClient\Exceptions;

class ConnectionException extends \RuntimeException
{
    public function __construct(string $message = 'Failed to connect to RPC server')
    {
        parent::__construct($message);
    }
}
