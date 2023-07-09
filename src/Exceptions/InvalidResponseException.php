<?php

declare(strict_types=1);

namespace Timiki\RpcClient\Exceptions;

class InvalidResponseException extends \RuntimeException
{
    public function __construct(string $message = 'Invalid response from RPC server')
    {
        parent::__construct($message);
    }
}
