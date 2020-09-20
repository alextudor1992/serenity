<?php

namespace Serenity\Routing;

use JsonException;
use Serenity\App;
use Serenity\Modules\Module;


/**
 * Class Request
 * @package Serenity\Routing
 */
class Request {
	public const METHOD_GET = 'GET';
	public const METHOD_POST = 'POST';
	public const METHOD_PUT = 'PUT';
	public const METHOD_DELETE = 'DELETE';
	public const METHOD_HEAD = 'HEAD';

	public const CONTENT_TYPE_JSON = 'application/json';
	public const CONTENT_TYPE_MULTIPART_DATA = 'multipart/form-data';
	public const CONTENT_TYPE_FORM = 'application/x-www-form-urlencoded';

	protected ?array $map;
	protected string $method;
	protected string $contentType;
	protected ?Module $service;
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
			->setContentType($_SERVER['CONTENT_TYPE'] ?? static::CONTENT_TYPE_JSON)
			->setRequestBody();

		/**
		 * If the current request was performed by another service,
		 * then we'll have the special HTTP headers available to
		 * detect easily which service should handle the request.
		 */
		$targetService = $_SERVER['HTTP_X_TARGET_SERVICE'] ?? null;

		if ($targetService) {
			$this->service = App::modules()->findByName($targetService);
			$this->serviceTag = $_SERVER['HTTP_X_TARGET_SERVICE_TAG'] ?? null;
		}
		$this->sourceService = $_SERVER['HTTP_X_SOURCE_SERVICE'] ?? null;
	}

    /**
     * @return string
     */
	public function getMethod(): string {
		return $this->method;
	}

	/**
	 * @param string $method
	 * @return $this
	 */
	protected function setMethod(string $method) {
		$this->method = $method;
		return $this;
	}

	/**
	 * @return string
	 */
	public function getContentType(): string {
		return $this->contentType;
	}

	/**
	 * @param string $contentType
	 * @return $this
	 */
	protected function setContentType(string $contentType) {
		$this->contentType = $contentType;
		return $this;
	}

	/**
	 * @param string $queryString
	 * @return string[]
	 */
	protected function parseQueryString(string $queryString): array {
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

        if (in_array($this->getMethod(), [self::METHOD_DELETE, self::METHOD_PUT, self::METHOD_POST], true)) {
	        switch ($_SERVER['CONTENT_TYPE']) {
		        case static::CONTENT_TYPE_JSON:
		        {
			        $input = file_get_contents('php://input');
			        $requestInput = json_decode($input, true, 512, JSON_THROW_ON_ERROR);
			        break;
		        }
		        case static::CONTENT_TYPE_MULTIPART_DATA:
		        case static::CONTENT_TYPE_FORM:
		        {
			        if (!empty($_REQUEST) && isset($_SERVER['CONTENT_TYPE'], $_SERVER['CONTENT_LENGTH']) && (int)$_SERVER['CONTENT_LENGTH']) {
				        parse_str($_REQUEST, $requestInput);
			        }
			        break;
		        }
		        default:
			        $requestInput = file_get_contents('php://input');
	        }

	        if (empty($_REQUEST)) {

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
	public function getBodyParam(string $param) {
		return $this->body[$param] ?? null;
	}

	/**
	 * @return Module|null
	 */
	public function getService(): ?Module {
		return $this->service;
	}

	/**
	 * @return string|null
	 */
	public function getServiceTag(): ?string {
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
