<?php

namespace Timiki\RpcClient\Event;

use Timiki\RpcCommon\JsonRequest;

class JsonRequestEvent extends AbstractEvent
{
    const EVENT = 'rpc.client.json.request';

    /**
     * @var JsonRequest
     */
    private $jsonRequest;

    /**
     * @param JsonRequest|JsonRequest[] $jsonRequests
     * @param null|string               $address
     */
    public function __construct($jsonRequests, $address = null)
    {
        $this->jsonRequest = $jsonRequests;
        parent::__construct($address);
    }

    /**
     * Get json request.
     *
     * @return JsonRequest|JsonRequest[]
     */
    public function getJsonRequest()
    {
        return $this->jsonRequest;
    }
}
