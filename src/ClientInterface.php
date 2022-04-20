<?php

namespace Timiki\RpcClient;

use GuzzleHttp\ClientInterface as HttpClientInterface;
use GuzzleHttp\Promise\Promise;
use Psr\EventDispatcher\EventDispatcherInterface;
use Timiki\RpcCommon\JsonRequest;
use Timiki\RpcCommon\JsonResponse;

interface ClientInterface
{
    /**
     * Set options.
     */
    public function setOptions(array $options = []): self;

    /**
     * Get http client.
     */
    public function getHttpClient(): HttpClientInterface;

    /**
     * Set event dispatcher.
     */
    public function setEventDispatcher(?EventDispatcherInterface $eventDispatcher): self;

    /**
     * Get RPC server address.
     */
    public function getAddress(): string;

    /**
     * Execute one RPC method without response.
     */
    public function notice(string $method, array $params = [], array $headers = []): void;

    /**
     * Execute one RPC method without response.
     */
    public function noticeAsync(string $method, array $params = [], array $headers = []): Promise;

    /**
     * Execute JSON-RPC method.
     */
    public function call(string $method, array $params = [], array $headers = []): JsonResponse;

    /**
     * Execute JSON-RPC method async.
     */
    public function callAsync(string $method, array $params = [], array $headers = []): Promise;

    /**
     * Execute json RPC request or batch requests.
     */
    public function execute(JsonRequest $request): Promise;
}
