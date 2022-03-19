<?php

namespace Vultr\VultrPhp\Services;

use Throwable;
use GuzzleHttp\Client;
use GuzzleHttp\RequestOptions;
use GuzzleHttp\Exception\RequestException;
use Psr\Http\Message\ResponseInterface;

use Vultr\VultrPhp\VultrException;
use Vultr\VultrPhp\VultrClient;
use Vultr\VultrPhp\Util\VultrUtil;
use Vultr\VultrPhp\Util\ModelInterface;
use Vultr\VultrPhp\Util\ListOptions;

abstract class VultrService
{
	protected VultrClient $vultr;
	private Client $client;

	public function __construct(VultrClient $vultr, Client $client)
	{
		$this->vultr = $vultr;
		$this->client = $client;
	}

	protected function getVultrClient() : VultrClient
	{
		return $this->vultr;
	}

	/**
	 * Get the Guzzle Client
	 */
	protected function getClient() : Client
	{
		return $this->client;
	}

	/**
	 * @param $uri - string - the url address to query after api.vultr.com/v2
	 * @param $model - ModelInterface - the object that will be mapped to the get response.
	 * @throws Child of VultrServiceObject
	 * @return ModelInterface
	 */
	protected function getObject(string $uri, ModelInterface $model) : ModelInterface
	{
		$exception_class = $model::class.'Exception';

		$underscored_class = $model->getResponseName();
		try
		{
			$response = $this->get($uri);
		}
		catch (VultrServiceException $e)
		{
			throw new $exception_class('Failed to get '.str_replace('_', ' ', $underscored_class). ' info: '.$e->getMessage(), $e->getHTTPCode(), $e);
		}

		return VultrUtil::convertJSONToObject($response->getBody(), clone $model, $underscored_class);
	}

	/**
	 * @param $uri - string - the url address to query after api.vultr.com/v2
	 * @param $model - ModelInterface - the object that will be mapped to the get response.
	 * @param $options - ListOptions - Pagination object
	 * @param $params - array - filter parameters.
	 * @throws Child of VultrServiceObject
	 * @return ModelInterface[]
	 */
	protected function getListObjects(string $uri, ModelInterface $model, ?ListOptions &$options = null, ?array $params = null) : array
	{
		if ($options === null)
		{
			$options = new ListOptions(100);
		}

		$exception_class = $model::class.'Exception';

		$objects = [];
		try
		{
			$objects = $this->list($uri, clone $model, $options);
		}
		catch (VultrServiceException $e)
		{
			throw new $exception_class('Failed to list '.$model->getResponseListName().': '.$e->getMessage(), $e->getHTTPCode(), $e);
		}

		return $objects;
	}

	/**
	 * @param $uri - string - anything after api.vultr.com/v2/
	 * @param $params - array|null - query parameters that will be added to the uri query stirng.
	 * @throws VultrServiceException
	 * @return ResponseInterface
	 */
	protected function delete(string $uri, ?array $params = []) : ResponseInterface
	{
		$options = [];
		if ($params !== null)
		{
			$options[RequestOptions::QUERY] = $params;
		}

		return $this->request('DELETE', $uri, $options);
	}

	/**
	 * @param $uri - string - anything after api.vultr.com/v2/
	 * @param $params - array - form data that will be encoded to a json
	 * @throws VultrServiceException
	 * @return ResponseInterface
	 */
	protected function post(string $uri, array $params = []) : ResponseInterface
	{
		return $this->request('POST', $uri, [RequestOptions::JSON => $params]);
	}

	/**
	 * @param $uri - string - anything after api.vultr.com/v2/
	 * @param $params - array - form data that will be encoded to a json
	 * @throws VultrServiceException
	 * @return ResponseInterface
	 */
	protected function put(string $uri, array $params = []) : ResponseInterface
	{
		return $this->request('PUT', $uri, [RequestOptions::JSON => $params]);
	}

	/**
	 * @param $uri - string - anything after api.vultr.com/v2/
	 * @param $params - array - form data that will be encoded to a json
	 * @throws VultrServiceException
	 * @return ResponseInterface
	 */
	protected function patch(string $uri, array $params = []) : ResponseInterface
	{
		return $this->request('PATCH', $uri, [RequestOptions::JSON => $params]);
	}

	/**
	 * @param $uri - string - anything after api.vultr.com/v2/
	 * @param $params - array|null - query parameters that will be added to the uri query stirng.
	 * @throws VultrServiceException
	 * @return ResponseInterface
	 */
	protected function get(string $uri, ?array $params = null) : ResponseInterface
	{
		$options = [];
		if ($params !== null)
		{
			$options[RequestOptions::QUERY] = $params;
		}

		return $this->request('GET', $uri, $options);
	}

	protected function list(string $uri, ModelInterface $model, ListOptions &$options, ?array $params = null) : array
	{
		try
		{
			if ($params === null)
			{
				$params = [];
			}
			$params['per_page'] = $options->getPerPage();

			if ($options->getCurrentCursor() != '')
			{
				$params['cursor'] = $options->getCurrentCursor();
			}

			$response = $this->get($uri, $params);
		}
		catch (VultrServiceException $e)
		{
			throw new VultrServiceException('Failed to list: '.$e->getMessage(), VultrException::SERVICE_CODE, $e->getHTTPCode(), $e);
		}

		$objects = [];
		try
		{
			$stdclass = json_decode($response->getBody());
			$options->setTotal($stdclass->meta->total);
			$options->setNextCursor($stdclass->meta->links->next);
			$options->setPrevCursor($stdclass->meta->links->prev);
			$list_name = $model->getResponseListName();
			foreach ($stdclass->$list_name as $object)
			{
				$objects[] = VultrUtil::mapObject($object, $model);
			}
		}
		catch (Throwable $e)
		{
			throw new VultrServiceException('Failed to deserialize list: '. $e->getMessage(), VultrException::SERVICE_CODE, null, $e);
		}

		return $objects;
	}

	private function request(string $method, string $uri, array $options = []) : ResponseInterface
	{
		try
		{
			$response = $this->getClient()->request($method, $uri, $options);
		}
		catch (RequestException $e)
		{
			$code = null;
			$message = $e->getMessage();
			if ($e->hasResponse())
			{
				$response = $e->getResponse();
				$error = json_decode($response->getBody(), true);
				$code = $response->getStatusCode();
				if (isset($error['error']))
				{
					$message = $error['error'];
				}
			}
			throw new VultrServiceException($method.' failed : '.$message, VultrException::SERVICE_CODE, $code, $e);
		}
		catch (Throwable $e)
		{
			throw new VultrServiceException($method.' fatal failed : '.$e->getMessage(), VultrException::SERVICE_CODE, null, $e);
		}

		return $response;
	}
}
