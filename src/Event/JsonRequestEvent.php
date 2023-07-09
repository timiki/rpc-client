<?php

declare(strict_types=1);

namespace Timiki\RpcClient\Event;

use Timiki\RpcCommon\JsonRequest;

class JsonRequestEvent extends AbstractEvent
{
    public function __construct(private readonly JsonRequest $jsonRequest)
    {
    }

    public function getJsonRequest(): JsonRequest
    {
        return $this->jsonRequest;
    }
}
