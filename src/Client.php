<?php

namespace Timiki\RpcClient;

use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\ClientInterface as HttpClientInterface;
use GuzzleHttp\Promise\Promise;
use GuzzleHttp\Psr7\Request as HttpRequest;
use Psr\EventDispatcher\EventDispatcherInterface;
use Timiki\RpcCommon\JsonRequest;
use Timiki\RpcCommon\JsonResponse;

/**
 * Light JSON-RPC client.
 */
class Client implements ClientInterface
{
    public const VERSION = '4.0.2';

    /**
     * Server address.
     */
    private string $address;

    /**
     * Options.
     */
    private array $options = [
        'attempts_on_error' => 10,
        'attempts_on_response_error' => false,
        'attempts_delay' => 1000, // msec
        'response_on_connection_exception' => true,
    ];

    private HttpClientInterface $httpClient;
    private ?EventDispatcherInterface $eventDispatcher = null;

    public function __construct(string $address, array $options = [], ?HttpClientInterface $httpClient = null)
    {
        $this->address = $address;
        $this->setOptions($options);

        if (null === $httpClient) {
            $httpClient = new HttpClient([
                'verify' => false,
            ]);
        }

        $this->httpClient = $httpClient;
    }

    /**
     * {@inheritDoc}
     */
    public function setOptions(array $options = []): self
    {
        foreach ($this->options as $option => $value) {
            if (isset($options[$option])) {
                $this->options[$option] = $options[$option];
            }
        }

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getHttpClient(): HttpClientInterface
    {
        return $this->httpClient;
    }

    /**
     * {@inheritDoc}
     */
    public function setEventDispatcher(?EventDispatcherInterface $eventDispatcher): self
    {
        $this->eventDispatcher = $eventDispatcher;

        return $this;
    }

    /**
     * {@inheritDoc}
     */
    public function getAddress(): string
    {
        return $this->address;
    }

    /**
     * {@inheritDoc}
     */
    public function notice(string $method, array $params = [], array $headers = []): void
    {
        $this->noticeAsync($method, $params, $headers)->wait();
    }

    /**
     * {@inheritDoc}
     */
    public function noticeAsync(string $method, array $params = [], array $headers = []): Promise
    {
        $request = new JsonRequest($method, $params);
        $request->headers()->add($headers);

        return $this->execute($request);
    }

    /**
     * {@inheritDoc}
     */
    public function call(string $method, array $params = [], array $headers = []): JsonResponse
    {
        return $this->callAsync($method, $params, $headers)->wait();
    }

    /**
     * {@inheritDoc}
     */
    public function callAsync(string $method, array $params = [], array $headers = []): Promise
    {
        $request = new JsonRequest($method, $params, uniqid(gethostname().'.', true));
        $request->headers()->add($headers);

        return $this->execute($request);
    }

    /**
     * {@inheritDoc}
     */
    public function execute(JsonRequest $request): Promise
    {
        if ($this->eventDispatcher) {
            $this->eventDispatcher->dispatch(new Event\JsonRequestEvent($request));
        }

        $headers = $request->headers()->all();

        $headers['user-agent'] = $headers['user-agent'] ?? 'JSON-RPC client/'.self::VERSION.'/'.PHP_VERSION;
        $headers['content-type'] = 'application/json';
        $headers['accept'] = 'application/json';
        $headers['cache-control'] = 'no-cache';
        $headers['connection'] = $headers['connection'] ?? 'close';

        $httpRequest = new HttpRequest(
            'POST',
            $this->address,
            $headers,
            json_encode($request, JSON_UNESCAPED_UNICODE)
        );

        $processRequest = function (HttpRequest $httpRequest, $attempt = 1) use (&$processRequest, &$request) {
            $httpPromise = $this->httpClient->sendAsync($httpRequest);

            $promise = new Promise(function () use (&$httpPromise, &$promise, &$processRequest, $httpRequest, $attempt, $request) {
                try {
                    $httpResponse = $httpPromise->wait();
                    $response = $this->parserHttp($httpResponse->getBody()->getContents());

                    if (!$request->getId()) {
                        $promise->resolve(null);

                        return;
                    }

                    if ($response->isError()
                        && (bool) $this->options['attempts_on_response_error']
                        && $attempt <= (int) $this->options['attempts_on_error']) {
                        usleep((int) $this->options['attempts_delay'] * 1000);

                        $promise->resolve($processRequest($httpRequest, ++$attempt));

                        return;
                    }

                    $response->setRequest($request);

                    if ($this->eventDispatcher) {
                        $this->eventDispatcher->dispatch(new Event\JsonResponseEvent($response));
                    }

                    $promise->resolve($response);
                } catch (\Throwable $e) {
                    if ($attempt <= (int) $this->options['attempts_on_error']) {
                        usleep((int) $this->options['attempts_delay'] * 1000);
                        $promise->resolve($processRequest($httpRequest, ++$attempt));
                    } else {
                        if ($this->options['response_on_connection_exception']) {
                            $response = new JsonResponse();
                            $response->setId($request->getId());
                            $response->setErrorMessage($e->getMessage());
                            $response->setErrorCode($e->getCode());

                            $promise->resolve($response);
                        } else {
                            throw new Exceptions\ConnectionException($e->getMessage());
                        }
                    }
                }
            });

            return $promise;
        };

        return $processRequest($httpRequest);
    }

    /**
     * Parser http response.
     */
    protected function parserHttp(string $http): JsonResponse
    {
        $json = json_decode($http, true);

        if (JSON_ERROR_NONE !== json_last_error() || empty($http)) {
            throw new Exceptions\InvalidResponseException('Invalid response from RPC server, must be valid json.');
        }

        if (
            \is_array($json)
            & \array_key_exists('id', $json)
            & (
                \array_key_exists('result', $json)
                || \array_key_exists('error', $json)
            )
        ) {
            $response = new JsonResponse();
            $response->setId($json['id'] ?? null);
            $response->setResult(\array_key_exists('result', $json) ? $json['result'] : null);

            if (\array_key_exists('error', $json)) {
                $response->setErrorCode(\array_key_exists('code', $json['error']) ? $json['error']['code'] : null);
                $response->setErrorMessage(\array_key_exists('message', $json['error']) ? $json['error']['message'] : null);
                $response->setErrorData(\array_key_exists('data', $json['error']) ? $json['error']['data'] : null);
            }

            return $response;
        }

        throw new Exceptions\InvalidResponseException('Invalid response format from RPC server.');
    }
}
