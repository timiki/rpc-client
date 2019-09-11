<?php

namespace Timiki\RpcClient;

use Doctrine\Common\Cache\Cache;
use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Psr7\Request as HttpRequest;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Timiki\RpcCommon\JsonRequest;
use Timiki\RpcCommon\JsonResponse;

/**
 * Light JSON-RPC client.
 */
class Client
{
    const VERSION = '3.2.0';

    /**
     * Server address.
     *
     * @var array
     */
    private $address = [];

    /**
     * Http client.
     *
     * @var HttpClient
     */
    private $httpClient;

    /**
     * Event dispatcher.
     *
     * @var null|EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * Options.
     *
     * @var array
     */
    private $options = [
        'attempts_on_error' => 10,
        'attempts_on_response_error' => false,
        'attempts_delay' => 1000,
    ];

    /**
     * @var null|Cache
     */
    private $cache;

    /**
     * Client constructor.
     *
     * @param array|string                  $address         RPC server address string or array
     * @param null|EventDispatcherInterface $eventDispatcher
     * @param array                         $options
     * @param null|Cache                    $cache
     */
    public function __construct($address, EventDispatcherInterface $eventDispatcher = null, $options = [], Cache $cache = null)
    {
        $this->setAddress($address);
        $this->setEventDispatcher($eventDispatcher);
        $this->setOptions($options);
        $this->httpClient = new HttpClient(['cookies' => true, 'verify' => false]);
        $this->cache = $cache;
    }

    /**
     * Set options.
     *
     * @param array $options
     *
     * @return \Timiki\RpcClient\Client
     */
    public function setOptions($options = [])
    {
        foreach ($this->options as $option => $value) {
            if (isset($options[$option])) {
                $this->options[$option] = $options[$option];
            }
        }

        return $this;
    }

    /**
     * Set RPC server address.
     *
     * @param array|string $address One or array of RPC server address for request
     *
     * @return $this
     */
    public function setAddress($address)
    {
        $this->address = \is_string($address) ? \explode(',', $address) : $address;

        return $this;
    }

    /**
     * Set event dispatcher.
     *
     * @param null|EventDispatcherInterface $eventDispatcher
     *
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
     * @param array  $params
     * @param array  $headers
     */
    public function notice($method, array $params = [], array $headers = [])
    {
        $request = new JsonRequest($method, $params);
        $request->headers()->add($headers);

        try {
            $this->execute($request);
        } catch (\Exception $e) {
            // Nothing
        }
    }

    /**
     * Execute JSON-RPC method.
     *
     * @param string $method
     * @param array  $params
     * @param array  $headers
     *
     * @return null|JsonResponse
     */
    public function call($method, array $params = [], array $headers = [])
    {
        $request = new JsonRequest($method, $params, \uniqid(\gethostname().'.', true));
        $request->headers()->add($headers);

        try {
            return $this->execute($request);
        } catch (\Exception $e) {
            return $this->createResponseFromException($e, $request);
        }
    }

    /**
     * Execute JSON-RPC method with cache.
     *
     * @param string      $method
     * @param array       $params
     * @param null|string $key
     * @param int         $lifetime
     * @param array       $headers
     *
     * @throws \Exception
     *
     * @return null|JsonResponse
     */
    public function callWithCache($method, array $params = [], $key = null, $lifetime = 3600, array $headers = [])
    {
        if (!$this->cache) {
            throw new \Exception('No set cache provider, set it in constructor');
        }

        if (empty($key)) {
            $key = \md5(\json_encode($this->getAddress()).'-'.$method.'-'.\json_encode($params));
        }

        if ($this->cache->contains($key)) {
            return \unserialize($this->cache->fetch($key));
        }

        $request = new JsonRequest($method, $params, \uniqid(\gethostname().'.', true));
        $request->headers()->add($headers);

        try {
            $response = $this->execute($request);
            $this->cache->save($key, \serialize($response), $lifetime);

            return $response;
        } catch (\Exception $e) {
            return $this->createResponseFromException($e, $request);
        }
    }

    /**
     * Create response from exception and request.
     *
     * @param \Exception  $e
     * @param JsonRequest $request
     *
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
     *
     * @throws Exceptions\InvalidResponseException
     *
     * @return JsonResponse|JsonResponse[]
     */
    protected function parserHttp($http)
    {
        $response = [];

        $json = \json_decode($http, true);

        if (JSON_ERROR_NONE !== \json_last_error() || empty($http)) {
            throw new Exceptions\InvalidResponseException('Invalid response from RPC server, must be valid json.');
        }

        /**
         * Create new JsonResponse.
         *
         * @param mixed $json
         *
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
                \is_array($json)
                & \array_key_exists('id', $json)
                & (
                    \array_key_exists('result', $json)
                    || \array_key_exists('error', $json)
                )
            ) {
                $id = $json['id'];
                $result = \array_key_exists('result', $json) ? $json['result'] : null;

                if (\array_key_exists('error', $json)) {
                    $error['code'] = \array_key_exists('code', $json['error']) ? $json['error']['code'] : null;
                    $error['message'] = \array_key_exists('message', $json['error']) ? $json['error']['message'] : null;
                    $error['data'] = \array_key_exists('data', $json['error']) ? $json['error']['data'] : null;
                }

                $response = new JsonResponse();

                $response->setId($id);
                $response->setResult($result);
                $response->setErrorCode($error['code']);
                $response->setErrorMessage($error['message']);
                $response->setErrorData($error['data']);

                return $response;
            }

            throw new Exceptions\InvalidResponseException('Invalid response format from RPC server.');
        };

        if (\array_keys($json) === \range(0, \count($json) - 1)) {
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
     * @param int                       $attempt
     *
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Exception
     *
     * @return null|JsonResponse|JsonResponse[]
     */
    public function execute($request, $attempt = 1)
    {
        // Check request
        if (\is_array($request)) {
            foreach ($request as $value) {
                if (!$value instanceof JsonRequest) {
                    throw new Exceptions\InvalidRequestException('Request must be array of JsonRequest objects');
                }
            }
        } elseif (!$request instanceof JsonRequest) {
            throw new Exceptions\InvalidRequestException('Request must instance of JsonRequest');
        }

        $address = 1 === \count($this->address) ? $this->address[0] : $this->address[\rand(0, \count($this->address))];
        $isNeedResponse = false;

        if ($this->eventDispatcher) {
            $event = $this->eventDispatcher->dispatch(new Event\JsonRequestEvent($request, $address));

            if ($event->isPropagationStopped()) {
                return null;
            }
        }

        if (\is_array($request)) {
            $requestHeaders = [];

            foreach ($request as $value) {
                $requestHeaders = \array_merge($requestHeaders, $value->headers()->all());
                $isNeedResponse = $isNeedResponse ? true : !empty($value->getId());
            }
        } else {
            $requestHeaders = $request->headers()->all();
            $isNeedResponse = !empty($request->getId());
        }

        // Default requestHeaders
        $requestHeaders['user-agent'] = \array_key_exists('user-agent', $requestHeaders)
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
            \trim($address),
            $requestHeaders,
            \json_encode($request, JSON_UNESCAPED_UNICODE)
        );

        try {
            $httpResponse = $this->httpClient->send($httpRequest);
            $response = $this->parserHttp($httpResponse->getBody()->getContents());

            if ($response->isError()
                && (bool) $this->options['attempts_on_response_error']
                && $attempt <= (int) $this->options['attempts_on_error']) {
                \usleep((int) $this->options['attempts_delay']);

                return $this->execute($request, ++$attempt);
            }
        } catch (\Exception $e) {
            if ($attempt <= (int) $this->options['attempts_on_error']) {
                \usleep((int) $this->options['attempts_delay']);

                return $this->execute($request, ++$attempt);
            }
            throw new Exceptions\ConnectionException($e->getMessage());
        }

        if (!$isNeedResponse) {
            return null;
        }

        // Set response headers
        if (\is_array($response)) {
            foreach ($response as $value) {
                $value->headers()->add($httpResponse->getHeaders());
            }
        } else {
            $response->headers()->add($httpResponse->getHeaders());
        }

        // Request <-> Response
        if (\is_array($response)) {
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
            $this->eventDispatcher->dispatch(new Event\JsonResponseEvent($response, $address));
        }

        return $response;
    }
}
