<?php

namespace Timiki\RpcClient;

use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Exception\ServerException;
use GuzzleHttp\Psr7\Request as HttpRequest;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Timiki\RpcCommon\JsonRequest;
use Timiki\RpcCommon\JsonResponse;

/**
 * Light Http RPC client
 */
class Client
{
    const VERSION = '3.0.6';

    /**
     * Server address.
     *
     * @var array
     */
    protected $address = [];

    /**
     * Http client.
     *
     * @var HttpClient
     */
    protected $httpClient;

    /**
     * Event dispatcher.
     *
     * @var EventDispatcherInterface|null
     */
    protected $eventDispatcher;

    /**
     * Client constructor.
     *
     * @param string|array $address RPC server address string or array
     * @param EventDispatcherInterface|null $eventDispatcher
     */
    public function __construct($address, EventDispatcherInterface $eventDispatcher = null)
    {
        $this->setAddress($address);
        $this->setEventDispatcher($eventDispatcher);
        $this->httpClient = new HttpClient(['cookies' => true, 'verify' => false]);
    }

    /**
     * Set RPC server address.
     *
     * @param string|array $address One or array of RPC server address for request
     * @return $this
     */
    public function setAddress($address)
    {
        $this->address = is_string($address) ? explode(',', $address) : (array)$address;

        return $this;
    }

    /**
     * Set event dispatcher.
     *
     * @param EventDispatcherInterface|null $eventDispatcher
     * @return $this
     */
    public function setEventDispatcher(EventDispatcherInterface $eventDispatcher = null)
    {
        $this->eventDispatcher = $eventDispatcher;

        return $this;
    }

    /**
     * Get RPC server address list.
     *
     * @return array
     */
    public function getAddress()
    {
        return $this->address;
    }

    /**
     * Execute one RPC method without response.
     *
     * @param string $method
     * @param array $params
     * @param array $headers
     *
     * @return void
     */
    public function notice($method, array $params = [], array $headers = [])
    {
        $request = new JsonRequest($method, $params);
        $request->headers()->add((array)$headers);

        try {
            $this->execute($request);
        } catch (\Exception $e) {
            // Nothing
        }
    }

    /**
     * Execute one RPC method.
     *
     * @param string $method
     * @param array $params
     * @param array $headers
     *
     * @return JsonResponse|null
     */
    public function call($method, array $params = [], array $headers = [])
    {
        $request = new JsonRequest($method, $params, uniqid(gethostname().'.', true));
        $request->headers()->add((array)$headers);

        try {
            return $this->execute($request);
        } catch (\Exception $e) {
            return $this->createResponseFromException($e, $request);
        }
    }

    /**
     * Create response from exception and request.
     *
     * @param \Exception $e
     * @param JsonRequest $request
     * @return JsonResponse
     */
    protected function createResponseFromException(\Exception $e, JsonRequest $request)
    {
        $response = new JsonResponse();
        $response->setId($request->getId());

        $response->setErrorMessage($e->getMessage());
        $response->setErrorCode($e->getCode());

        return $response;
    }

    /**
     * Parser http response.
     *
     * @param string $http
     * @return JsonResponse[]|JsonResponse
     * @throws Exceptions\InvalidResponseException
     */
    protected function parserHttp($http)
    {
        $response = [];

        $json = json_decode($http, true);

        if (json_last_error() !== JSON_ERROR_NONE || empty($http)) {
            throw new Exceptions\InvalidResponseException('Invalid response from RPC server, must be valid json.');
        }

        /**
         * Create new JsonResponse
         *
         * @param $json
         * @return JsonResponse
         */
        $createJsonResponse = function ($json) {

            $id = null;
            $result = null;
            $error = [
                'code' => null,
                'message' => null,
                'data' => null,
            ];

            if (
                is_array($json)
                & array_key_exists('id', $json)
                & (
                    array_key_exists('result', $json)
                    || array_key_exists('error', $json)
                )
            ) {

                $id = $json['id'];
                $result = array_key_exists('result', $json) ? $json['result'] : null;

                if (array_key_exists('error', $json)) {
                    $error['code'] = array_key_exists('code', $json['error']) ? $json['error']['code'] : null;
                    $error['message'] = array_key_exists('message', $json['error']) ? $json['error']['message'] : null;
                    $error['data'] = array_key_exists('data', $json['error']) ? $json['error']['data'] : null;
                }

                $response = new JsonResponse();

                $response->setId($id);
                $response->setResult($result);
                $response->setErrorCode($error['code']);
                $response->setErrorMessage($error['message']);
                $response->setErrorData($error['data']);

                return $response;

            } else {
                throw new Exceptions\InvalidResponseException('Invalid response format from RPC server.');
            }

        };

        if (array_keys($json) === range(0, count($json) - 1)) {
            foreach ($json as $part) {
                $response[] = $createJsonResponse($part);
            }
        } else {
            $response = $createJsonResponse($json);
        }

        return $response;
    }

    /**
     * Execute json RPC request or batch requests.
     *
     * @param JsonRequest|JsonRequest[] $request
     * @return JsonResponse|JsonResponse[]|null
     *
     * @throws Exceptions\ConnectionException
     * @throws Exceptions\InvalidRequestException
     */
    public function execute($request)
    {
        // Check request
        if (is_array($request)) {
            foreach ($request as $value) {
                if (!$value instanceof JsonRequest) {
                    throw new Exceptions\InvalidRequestException('$request must be array of JsonRequest objects');
                }
            }
        } elseif (!$request instanceof JsonRequest) {
            throw new Exceptions\InvalidRequestException('$request must instance of JsonRequest');
        }

        $address = count($this->address) === 1 ? $this->address[0] : $this->address[rand(0, count($this->address))];
        $isNeedResponse = true;

        if ($this->eventDispatcher) {

            $event = $this->eventDispatcher->dispatch(
                Event\JsonRequestEvent::EVENT,
                new Event\JsonRequestEvent($request, $address)
            );

            if ($event->isPropagationStopped()) {
                return null;
            }
        }

        if (is_array($request)) {
            $requestHeaders = [];

            foreach ($request as $value) {
                $requestHeaders = array_merge($requestHeaders, $value->headers()->all());
                $isNeedResponse = empty($value->getId()) ? false : true;
            }

        } else {
            $requestHeaders = $request->headers()->all();
            $isNeedResponse = empty($request->getId()) ? false : true;
        }

        // Default requestHeaders
        $requestHeaders['user-agent'] = array_key_exists('user-agent', $requestHeaders)
            ? $requestHeaders['user-agent'] : 'JSON-RPC client/'.self::VERSION.'/'.PHP_VERSION;

        $requestHeaders['content-type'] = 'application/json';
        $requestHeaders['accept'] = 'application/json';
        $requestHeaders['cache-control'] = 'no-cache';
        $requestHeaders['connection'] = 'close';

        if (empty($this->address)) {
            throw new Exceptions\ConnectionException('Must be set rpc server address');
        }

        $httpRequest = new HttpRequest(
            'POST',
            trim($address),
            $requestHeaders,
            json_encode($request, JSON_UNESCAPED_UNICODE)
        );

        try {
            $httpResponse = $this->httpClient->send($httpRequest);
            $response = $this->parserHttp($httpResponse->getBody()->getContents());
        } catch (\Exception $e) {
            throw new Exceptions\ConnectionException($e->getMessage());
        }

        if (!$isNeedResponse) {
            return null;
        }

        // Set response headers
        if (is_array($response)) {
            foreach ($response as $value) {
                $value->headers()->add($httpResponse->getHeaders());
            }
        } else {
            $response->headers()->add($httpResponse->getHeaders());
        }

        // Request <-> Response
        if (is_array($response)) {
            foreach ($response as $res) {

                foreach ($request as $req) {
                    if ($req->getId() === $res->getId()) {
                        $req->setResponse($res);
                    }
                }

            }
        } else {
            $request->setResponse($response);
        }

        if ($this->eventDispatcher) {
            $this->eventDispatcher->dispatch(
                Event\JsonResponseEvent::EVENT,
                new Event\JsonResponseEvent($response, $address)
            );
        }

        return $response;
    }
}
