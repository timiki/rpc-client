<?php

include "../vendor/autoload.php";

$client = new \Timiki\RpcClient\Client('https://rpc.publicator.me/v2');
$request = new \Timiki\RpcCommon\JsonRequest('system.ping', [], 1);

$response = $client->call('system.ping');

echo '---------------' . PHP_EOL;
echo 'single requests' . PHP_EOL;
echo '---------------' . PHP_EOL;

$time = microtime(true);
$client->callAsync('system.ping')->wait();
echo 'async: ' . (microtime(true) - $time) * 1000 . PHP_EOL;

$time = microtime(true);
$client->call('system.ping');
echo 'sync: ' . (microtime(true) - $time) * 1000 . PHP_EOL;

echo '---------------' . PHP_EOL;
echo 'multi requests' . PHP_EOL;
echo '---------------' . PHP_EOL;

// Async
$response = [];
$time = microtime(true);

while (count($response) < 5) {
    $response[] = $client->callAsync('system.ping');
}

$response = \GuzzleHttp\Promise\Utils::all($response)->wait();
echo 'async: ' . (microtime(true) - $time) * 1000 . PHP_EOL;

// Sync
$response = [];
$time = microtime(true);

while (count($response) < 5) {
    $response[] = $client->call('system.ping');
}

echo 'sync: ' . (microtime(true) - $time) * 1000 . PHP_EOL;

////$promise2 = $client->execute($request);
////$promise3 = $client->execute($request);
//
//$response = \GuzzleHttp\Promise\Utils::all($response)->wait();
//
////$response = $promise->wait();
//
//var_dump(array_map(function ($r) {
//    return $r->getResult();
//}, $response));