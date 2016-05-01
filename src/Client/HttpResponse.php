<?php

namespace Timiki\RpcClient\Client;

/**
 * Http response
 */
class HttpResponse
{
	/**
	 * Headers
	 *
	 * @var array
	 */
	protected $headers;

	/**
	 * Body
	 *
	 * @var string
	 */
	protected $body;

	/**
	 * Code
	 *
	 * @var integer
	 */
	protected $code;

	/**
	 * Info
	 *
	 * @var array
	 */
	protected $info = [];

	/**
	 * Http constructor
	 *
	 * @param string $body    Body response string
	 * @param string $headers Headers response string
	 * @param array  $info    Curl info for response
	 */
	public function __construct($body, $headers = '', array $info = [])
	{
		$this->body = $body;
		$this->info = $info;
		$this->parseHeaders($headers);
	}

	/**
	 * Parse message headers
	 *
	 * @param string $headers Headers string
	 * @return array
	 */
	protected function parseHeaders($headers)
	{
		if (!empty($headers)) {
			$result = [];
			foreach (explode("\r\n", $headers) as $i => $line) {
				if (!empty($line)) {
					$parse = explode(': ', $line);
					if (count($parse) === 2) {
						$result[strtolower($parse[0])] = [$parse[1]];
					}
				}
			}
			$this->headers = $result;
		}
	}

	/**
	 * Gets headers array
	 *
	 * @return array
	 */
	public function getHeaders()
	{
		return $this->headers;
	}

	/**
	 * Get header by name
	 *
	 * @param string            $name    Header name
	 * @param null|string|array $default Default header values if header not set
	 * @return array|null
	 */
	public function getHeader($name, $default = null)
	{
		if (array_key_exists(strtolower($name), $this->headers)) {
			return $this->headers[strtolower($name)];
		}

		return $default;
	}

	/**
	 * Get body
	 *
	 * @return string
	 */
	public function getBody()
	{
		return $this->body;
	}

	/**
	 * Get curl info
	 *
	 * @return array
	 */
	public function getInfo()
	{
		return $this->info;
	}
}

