<?php

namespace Serenity\Application\Routing\Network;

class CSP implements HTTPHeaderInterface {
	public const BASE_URI = 'base-uri';
	public const CONNECTIONS = 'connect-src';
	public const CHILD_IFRAME = 'child-src';
	public const SCRIPTS = 'script-src';
	public const STYLE = 'style-src';
	public const IMAGES = 'img-src';
	public const FORMS_ACTION = 'form-action';
	public const ANCESTOR_IFRAME	= 'frame-ancestors';
	public const FONTS				= 'font-src';
	public const OBJECTS			= 'object-src';
	public const MEDIA				= 'media-src';

	public const ANY	= '*';
	public const SELF	= "'self'";
	public const NONE	= "'none'";

	protected array $rules = [];

    /**
     * @param string $contentType
     * @param string $target
     * @return $this
     */
	public function addTargetToWhitelist(string $contentType, string $target)
	{
	    if (in_array($target, [self::ANY, self::SELF, self::NONE], true))
        {
            $this->rules[$contentType] = [$target];
        }
	    else
        {
            $this->rules[$contentType][] = $target;
        }
	    return $this;
	}

    /**
     * @param string $contentType
     * @param string $target
     * @return $this
     */
	public function removeTargetFromWhitelist(string $contentType, string $target)
    {
        if (isset($this->rules[$contentType]))
        {
            array_splice($this->rules[$contentType], array_search($target, $this->rules[$contentType], true), 1);
        }
        return $this;
    }

    /**
     * @return array
     */
	public function getHeaders() : array
	{
        $headers = [];

        foreach ($this->rules as $rule => $whitelist)
        {
            $headers[] = $rule.' '.implode(' ', $whitelist);
        }
        return !empty($headers) ? ['Content-Security-Policy' => implode(', ', $headers)] : [];
	}
}