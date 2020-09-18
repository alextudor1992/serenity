<?php

namespace Serenity\Routing;

use Serenity\Routing\Network\HTTPHeaderInterface;

class Redirect implements HTTPHeaderInterface
{
	/** This will instruct Nginx / OpenResty to make an internal redirect. */
	public const INTERNAL = 0;

	/** HTTP response code for permanent redirect. */
	public const PERMANENT = 301;

	/** HTTP response code for temporary redirect */
	public const TEMPORARY = 302;

	/** This will instruct Nginx / OpenResty to send the same request to another upstream */
	public const NEXT_UPSTREAM = 502;

	public int $type;
	public string $targetUrl;

	public function __construct(string $request_url, int $type=self::TEMPORARY)
	{
		if (in_array($type, [self::INTERNAL, self::PERMANENT, self::TEMPORARY, self::NEXT_UPSTREAM], true))
		{
			$this->type = $type;
		}
		$this->targetUrl = $request_url;
	}

	public function getRedirectType(): int
	{
		return $this->type;
	}

	public function getTargetUrl(): string
	{
		return $this->targetUrl;
	}

    /**
     * @inheritDoc
     */
    public function getHeaders(): array
    {
        $headers = [];
        $redirectUrl = $this->getTargetURL();

        switch ($this->getRedirectType())
        {
            case static::INTERNAL:
            {
                $headers['X-Accel-Redirect'] = $redirectUrl;
                break;
            }
            case static::PERMANENT:
            case static::TEMPORARY:
            {
                $headers['Location'] = $redirectUrl;
                break;
            }
        }
        return $headers;
    }
}
