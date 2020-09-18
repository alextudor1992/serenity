<?php

namespace Serenity\Routing;

use JsonException;

/**
 * Class Request
 * @package Serenity\Routing
 */
class Request
{
    public const METHOD_GET = 'GET';
    public const METHOD_POST = 'POST';
    public const METHOD_PUT = 'PUT';
    public const METHOD_DELETE = 'DELETE';
    public const METHOD_HEAD = 'HEAD';

	protected ?array $map;
    protected string $method;
	protected ?string $service;
	protected ?string $serviceTag;
    protected ?string $sourceService;
	protected ?array $queryParams;
	protected ?string $requestPath;

	/** @var mixed */
	protected $body;

    /**
     * Request constructor.
     * @param string|null $requestURI
     * @throws JsonException
     */
	public function __construct(string $requestURI=null)
	{
		if ($requestURI)
		{
			$urlPath = urldecode($requestURI);
			$urlParts = parse_url($urlPath);

			$this->requestPath = $urlParts['path'] ?? '/';
            $this->queryParams = !empty($urlParts['query']) ? $this->parseQueryString($urlParts['query']) : null;
		}

		$this->setMethod($_SERVER['REQUEST_METHOD'])
		    ->setRequestBody();

        /**
         * If the current request was performed by another service,
         * then we'll have the special HTTP headers available to
         * detect easily which service should handle the request.
         */
		$this->service = $_SERVER['HTTP_X_TARGET_SERVICE'] ?? null;
		$this->serviceTag = $_SERVER['HTTP_X_TARGET_SERVICE_TAG'] ?? null;
		$this->sourceService = $_SERVER['HTTP_X_SOURCE_SERVICE'] ?? null;
	}

    /**
     * @return string
     */
	public function getMethod(): string
    {
		return $this->method;
	}

    /**
     * @param string $method
     * @return $this
     */
	public function setMethod(string $method)
    {
        $this->method = $method;
        return $this;
    }

    /**
     * @param string $queryString
     * @return string[]
     */
    protected function parseQueryString(string $queryString): array
    {
        $queryParams = [];
        $stringParams = explode('&', $queryString);

        foreach ($stringParams as $stringParam)
        {
            $queryParam = explode('=', $stringParam);
            $queryParams[trim($queryParam[0])] = trim($queryParam[1]);
        }
        return $queryParams;
    }

    /**
     * If the server received raw JSON or encoded form, as body of the request,
     * we'll decode the body and map it to the $_REQUEST.
     *
     * @throws JsonException
     * @return mixed
     */
    protected function parseRequestInput()
    {
        $requestInput = null;

        if (in_array($this->getMethod(), [self::METHOD_DELETE, self::METHOD_PUT, self::METHOD_POST], true))
        {
            if (empty($_REQUEST) && isset($_SERVER['CONTENT_TYPE'], $_SERVER['CONTENT_LENGTH']) && (int)$_SERVER['CONTENT_LENGTH'])
            {
                $input = file_get_contents('php://input');

                switch ($_SERVER['CONTENT_TYPE'])
                {
                    case 'application/json':
                    {
                        $requestInput = json_decode($input, true, 512, JSON_THROW_ON_ERROR);
                        break;
                    }
                    case 'multipart/form-data':
                    case 'application/x-www-form-urlencoded':
                    {
                        parse_str($input, $requestInput);
                        break;
                    }
                    default: $requestInput = $input;
                }
            }
        }
        return $requestInput;
    }

    /**
     * @param string|null $body
     * @return $this
     * @throws JsonException
     */
	public function setRequestBody(string $body=null)
	{
        $this->body = $body ?: $this->parseRequestInput();
        return $this;
	}

	/**
	 * @param string $paramName
	 * @return null|string
	 */
	public function getParam(string $paramName) :? string
	{
		return $this->map[$paramName] ?? null;
	}

    /**
     * @param array $path
     * @return $this
     */
	public function setPath(array $path)
	{
		$this->map = $path;
		return $this;
	}

    /**
     * @param string $property
     * @return string|null
     */
	public function getQueryParam(string $property) :? string
	{
		return $this->queryParams[$property] ?? null;
	}

    /**
     * @return mixed
     */
	public function getBody()
	{
		return $this->body;
	}

	/**
	 * @param string $param
	 * @return mixed|null
	 */
	public function getBodyParam(string $param)
	{
		return $this->body[$param] ?? null;
	}

    /**
     * @return string|null
     */
	public function getService() :? string
	{
		return $this->service;
	}

    /**
     * @return string|null
     */
	public function getServiceTag() :? string
	{
		return $this->serviceTag;
	}

    /**
     * @return string|null
     */
	public function getRequestPath() :? string
	{
		return $this->requestPath;
	}
}
