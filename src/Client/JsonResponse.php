<?php

namespace Timiki\RpcClient\Client;

/**
 * Client response
 */
class JsonResponse
{
	/**
	 * Response
	 *
	 * @var HttpResponse
	 */
	private $httpResponse;

	/**
	 * Response constructor
	 *
	 * @param HttpResponse $httpResponse
	 */
	public function __construct(HttpResponse $httpResponse)
	{
		$this->httpResponse = $httpResponse;
	}

	/**
	 * Get http response object
	 *
	 * @return HttpResponse
	 */
	public function getHttpResponse()
	{
		return $this->httpResponse;
	}

	/**
	 * Get json response
	 *
	 * @return array|null
	 */
	public function getResponse()
	{
		$json = null;

		if (!empty($this->httpResponse->getBody())) {

			try {
				$json = json_decode($this->httpResponse->getBody());
			} catch (\Exception $e) {
				$json = null;
			}

		}

		return $json;
	}

	/**
	 * Get id
	 *
	 * @return integer|null
	 */
	public function getId()
	{
		if ($response = $this->getResponse()) {
			return array_key_exists('id', $response) ? $response['id'] : null;
		}

		return null;
	}

	/**
	 * Get error
	 *
	 * @return array|null
	 */
	public function getError()
	{
		if ($response = $this->getResponse()) {
			return array_key_exists('error', $response) ? $response['error'] : null;
		}

		return null;
	}

	/**
	 * Get result
	 *
	 * @return mixed|null
	 */
	public function getResult()
	{
		if ($response = $this->getResponse()) {
			return array_key_exists('result', $response) ? $response['result'] : null;
		}

		return null;
	}

	/**
	 * Is response
	 *
	 * @return array
	 */
	public function isResponse()
	{
		return $this->getResponse() !== null;
	}

	/**
	 * Is result
	 *
	 * @return array
	 */
	public function isResult()
	{
		return $this->getResult() !== null;
	}

	/**
	 * Is error
	 *
	 * @return array
	 */
	public function isError()
	{
		return $this->getError() !== null;
	}
}

