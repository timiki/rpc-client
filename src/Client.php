<?php

namespace Timiki\RpcClient;

use Timiki\RpcClient\Client\JsonResponse;
use Timiki\RpcClient\Client\Http;

/**
 * Client class
 */
class Client
{
	const VERSION = '1.0';

	/**
	 * Server address
	 *
	 * @var array
	 */
	protected $address = [];

	/**
	 * Client default headers
	 *
	 * @var array
	 */
	protected $headers = [];

	/**
	 * Client default cookies
	 *
	 * @var array
	 */
	protected $cookies = [];

	/**
	 * Create new client
	 *
	 * @param string|array $address RPC address string or array
	 * @param array        $headers Headers array
	 * @param array        $cookies Cookies array
	 */
	public function __construct(array $address, array $headers = [], array $cookies = [])
	{
		$this->setHeader('User-Agent', 'JSON-RPC client '.self::VERSION.' (php '.PHP_VERSION.')');
		$this->setHeader('Content-Type', 'application/json');

		$this->addAddress($address);
		$this->setHeaders($headers);
		$this->setCookies($cookies);
	}

	/**
	 * Set client header
	 *
	 * @param string       $name  Header name
	 * @param string|array $value Header value
	 * @param bool         $replace
	 * @return $this
	 */
	public function setHeader($name, $value, $replace = false)
	{
		if (!is_array($value)) {
			$value = [$value];
		}
		if (array_key_exists($name, $this->headers)) {
			if ($replace) {
				$this->headers[$name] = $value;
			} else {
				$this->headers[$name] = array_merge($this->headers[$name], $value);
			}
		} else {
			$this->headers[$name] = $value;
		}

		return $this;
	}

	/**
	 * Set client headers by array
	 *
	 * @param array $headers Headers array
	 * @return $this
	 */
	public function setHeaders(array $headers)
	{
		foreach ($headers as $name => $value) {
			$this->setHeader($name, $value);
		}

		return $this;
	}

	/**
	 * Get headers
	 *
	 * @return array
	 */
	public function getHeaders()
	{
		return $this->headers;
	}

	/**
	 * Set client cookie
	 *
	 * @param string $name  Cookie name
	 * @param string $value Cookie value
	 * @return $this
	 */
	public function setCookie($name, $value)
	{
		$this->cookies[$name] = $value;

		return $this;
	}

	/**
	 * Set client cookies by array
	 *
	 * @param array $cookies Cookies
	 * @return $this
	 */
	public function setCookies(array $cookies)
	{
		foreach ($cookies as $name => $value) {
			$this->setCookie($name, $value);
		}

		return $this;
	}

	/**
	 * Get client cookies
	 *
	 * @return array
	 */
	public function getCookies()
	{
		return $this->cookies;
	}

	/**
	 * Add server address
	 *
	 * @param string|array $address
	 * @param bool         $append
	 * @return $this
	 */
	public function addAddress($address, $append = true)
	{
		if ($append === false) {
			$this->address = [];
		}
		if (is_array($address)) {
			foreach ($address as $path) {
				$this->addAddress($path);
			}
		}
		if (is_string($address)) {
			$this->address[] = $address;
		}

		return $this;
	}

	/**
	 * Get server address for request
	 *
	 * @return string|null
	 */
	public function getAddressForRequest()
	{
		if (count($this->address) === 0) {
			return null;
		}
		if (count($this->address) === 1) {
			return $this->address[0];
		}

		return $this->address[rand(0, count($this->address))];
	}

	/**
	 * Get server address list
	 *
	 * @return array
	 */
	public function getAddress()
	{
		return $this->address;
	}

	/**
	 * Call method
	 *
	 * @param string  $method
	 * @param array   $params
	 * @param integer $id
	 * @return JsonResponse
	 */
	public function call($method, array $params = [], $id = null)
	{
		$headers = $this->getHeaders();
		$cookies = $this->getCookies();


		$body         = json_encode(['jsonrpc' => '2.0', 'method' => $method, 'params' => $params, 'id' => $id]);
		$curl         = new Http();
		$httpResponse = $curl->post($this->getAddressForRequest(), $body, $headers, $cookies);

		return new JsonResponse($httpResponse);
	}
}
