<?php

namespace Timiki\Bundle\RpcServerBundle\Event;

use Timiki\RpcCommon\JsonResponse;

class JsonResponseEvent extends AbstractEvent
{
    const EVENT = 'rpc.client.json.response';

    /**
     * @var JsonResponse
     */
    private $jsonResponse;

    /**
     * @param JsonResponse|JsonResponse[] $jsonRequests
     * @param null $address
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