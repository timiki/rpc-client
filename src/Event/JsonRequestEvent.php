<?php

namespace Timiki\RpcClient\Event;

use Timiki\RpcCommon\JsonRequest;

class JsonRequestEvent extends AbstractEvent
{
    private JsonRequest $jsonRequest;

    public function __construct(JsonRequest $jsonRequests)
    {
        $this->jsonRequest = $jsonRequests;
    }

    public function getJsonRequest(): JsonRequest
    {
        return $this->jsonRequest;
    }
}
