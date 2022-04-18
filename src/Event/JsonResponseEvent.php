<?php

namespace Timiki\RpcClient\Event;

use Timiki\RpcCommon\JsonResponse;

class JsonResponseEvent extends AbstractEvent
{
    private JsonResponse $jsonResponse;

    public function __construct(JsonResponse $jsonRequests)
    {
        $this->jsonResponse = $jsonRequests;
    }

    public function getJsonResponse(): JsonResponse
    {
        return $this->jsonResponse;
    }
}
