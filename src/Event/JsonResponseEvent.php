<?php

namespace Timiki\RpcClient\Event;

use Timiki\RpcCommon\JsonResponse;

class JsonResponseEvent extends AbstractEvent
{
    /**
     * @var JsonResponse
     */
    private $jsonResponse;

    /**
     * @param JsonResponse|JsonResponse[] $jsonRequests
     * @param null                        $address
     */
    public function __construct($jsonRequests, $address = null)
    {
        $this->jsonResponse = $jsonRequests;
        parent::__construct($address);
    }

    /**
     * Get json response.
     *
     * @return JsonResponse|JsonResponse[]
     */
    public function getJsonResponse()
    {
        return $this->jsonResponse;
    }
}
