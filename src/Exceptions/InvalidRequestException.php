<?php

declare(strict_types=1);

namespace Timiki\RpcClient\Exceptions;

class InvalidRequestException extends \RuntimeException
{
    public function __construct(string $message = 'Invalid RPC request')
    {
        parent::__construct($message);
    }
}
