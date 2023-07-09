Simple JSON-RPC Http client
===========================

Install
-------

    composer require timiki/rpc-client

Options
-------

**attempts_on_error** (int) - Count of attempts on connection or response  error (default: 10)

**attempts_on_response_error** (bool) - Attempt on response error  (default: false)

**attempts_delay** (int) - Delay in msec between attempts (default: 1000)

**response_on_connection_exception** (bool) - If set true client return JsonResponse on connection exception (default: true)

Async
-------

JSON-RPC Http client use [guzzlehttp/promises](https://github.com/guzzle/promises) for call async function

**callAsync**(string $method, array $params = [], array $headers = []): Promise

**noticeAsync**(string $method, array $params = [], array $headers = []): Promise
