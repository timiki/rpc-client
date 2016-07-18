<?php

namespace Timiki\RpcClient;

use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Psr7\Request as HttpRequest;

/**
 * Light Http RPC client
 */
class Client
{
    const VERSION = '2.0';

    /**
     * Internal ids count.
     *
     * @var integer
     */
    protected static $ids = 0;

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
     * Client constructor.
     *
     * @param string|array $address RPC server address string or array
     */
    public function __construct($address)
    {
        $this->setAddress($address);
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
        $this->address = (array)$address;

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
     * @param array  $params
     *
     * @return void
     */
    public function notice($method, array $params = [])
    {
        $request = new JsonRequest($method, $params);

        $this->execute($request);
    }

    /**
     * Execute one RPC method.
     *
     * @param string $method
     * @param array  $params
     *
     * @return JsonResponse|null
     */
    public function call($method, array $params = [])
    {
        $request = new JsonRequest($method, $params, ++self::$ids);

        return $this->execute($request);
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

            $id     = null;
            $result = null;
            $error  = [
                'code'    => null,
                'message' => null,
                'data'    => null,
            ];

            if (
                is_array($json)
                & array_key_exists('id', $json)
                & (
                    array_key_exists('result', $json)
                    || array_key_exists('error', $json)
                )
            ) {

                $id     = $json['id'];
                $result = array_key_exists('result', $json) ? $json['result'] : null;

                if (array_key_exists('error', $json)) {
                    $error['code']    = array_key_exists('code', $json['error']) ? $json['error']['code'] : null;
                    $error['message'] = array_key_exists('message', $json['error']) ? $json['error']['message'] : null;
                    $error['data']    = array_key_exists('data', $json['error']) ? $json['error']['data'] : null;
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

        $isNeedResponse = true;

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
        $requestHeaders['user-agent']   = array_key_exists('user-agent', $requestHeaders)
            ? $requestHeaders['user-agent'] : 'JSON-RPC client '.self::VERSION.'.'.PHP_VERSION;

        $requestHeaders['content-type'] = 'application/json';

        if (empty($this->address)) {

            throw new Exceptions\ConnectionException('Must be set rpc server address');

        }

        // If set more than one rpc server address, select random for request
        $address = count($this->address) === 1
            ? $this->address[0] : $this->address[rand(0, count($this->address))];

        $httpRequest = new HttpRequest('POST', $address, $requestHeaders, json_encode($request));

        try {

            // Try send request to RPC server

            $httpResponse = $this->httpClient->send($httpRequest);

        } catch (\Exception $e) {

            throw new Exceptions\ConnectionException($e->getMessage());

        }

        if (!$isNeedResponse) {
            return null;
        }

        // JsonResponse
        $response = $this->parserHttp($httpResponse->getBody()->getContents());

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

        return $response;
    }
}
