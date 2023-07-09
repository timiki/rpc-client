<?php

declare(strict_types=1);

namespace Timiki\RpcClient\Event;

use Timiki\RpcCommon\JsonResponse;

class JsonResponseEvent extends AbstractEvent
{
    public function __construct(private readonly JsonResponse $jsonResponse)
    {
    }

    public function getJsonResponse(): JsonResponse
    {
        return $this->jsonResponse;
    }
}
