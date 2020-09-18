<?php

namespace Serenity\Routing;

use Serenity\App;
use Serenity\Routing\Network\HTTPHeaderInterface;
use function \
{
	header,
	headers_sent
};

/**
 * Class Response
 * @package Serenity\Routing
 */
class Response implements HTTPHeaderInterface
{
    public const CODE_SUCCESS = 200;
    public const CODE_SUCCESS_EMPTY = 204;
    public const CODE_NOT_FOUND = 404;

	public const TYPE_JSON = 'json';

	/** HTTP Response code. */
    protected int $code = self::CODE_SUCCESS_EMPTY;

	/** Response data. */
	protected ?string $data;

	/** @var HTTPHeaderInterface[] */
	protected array $networkConfigurations;

	/**
	 * Response constructor.
	 * @param string|null $data
     * @param int $code
	 * @param string|null $type
	 */
	public function __construct(string $data=null, int $code=200, string $type=null)
	{
	    $this->setHTTPCode($code)
            ->setBody($data)
            ->setType($type);
	}

    /**
     * @param string $type
     * @return $this;
     */
	public function setType(string $type)
	{
		if (!headers_sent())
		{
			$mime_types	= App::config()->get('accepted_mime_types');
			$mime_type	= $mime_types[$type] ?? $type;
			header("Content-Type: $mime_type");
		}
		return $this;
	}

    /**
     * @param int $code
     * @return $this
     */
	public function setHTTPCode(int $code)
	{
		$this->code = $code;
		return $this;
	}

    /**
     * @return int
     */
	public function getHTTPCode(): int
	{
		return $this->code;
	}

    /**
     * @param string $body
     * @return $this
     */
	public function setBody(string $body)
	{
		$this->data = $body;
		return $this;
	}

    /**
     * @return string|null
     */
	public function getBody(): ?string
	{
		return $this->data;
	}

    /**
     * @param HTTPHeaderInterface $networkConfiguration
     * @return $this
     */
	public function addNetworkConfiguration(HTTPHeaderInterface  $networkConfiguration)
    {
        $this->networkConfigurations[] = $networkConfiguration;
        return $this;
    }

    /**
     * @return array
     */
	public function getHeaders(): array
    {
        $headers = [];

        foreach ($this->networkConfigurations as $networkConfiguration)
        {
            $headers[] = $networkConfiguration->getHeaders();
        }
        return array_merge(...$headers);
    }
}
