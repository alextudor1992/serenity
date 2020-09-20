<?php

namespace Serenity\Application\Routing\Network;

use function gmdate;
use function strtotime;


/**
 * Class Cache
 * @package Serenity\Routing\Network
 */
class Cache implements HTTPHeaderInterface {
	public const PUBLIC = 'public';
	public const PRIVATE = 'private';
	public const IMMUTABLE		= 'immutable';
	public const NO_TRANSFORM	= 'no-transform';
	public const ONLY_IF_CACHED	= 'only-if-cached';

    public const NO_CACHE  = 'no-cache';
    public const NO_STORE = 'no-store';
    public const MUST_REVALIDATE = 'must-revalidate';

    protected int $lifetime = 60;
	protected array $cacheRules = [self::PUBLIC];

    /**
     * @param int $lifetime
     * @return $this
     */
	public function setLifetime(int $lifetime)
    {
        $this->lifetime = $lifetime;
        return $this;
    }

    /**
     * @param array $cacheRules
     * @return $this
     */
    public function setCacheRules(array $cacheRules)
    {
        $this->cacheRules = $cacheRules;
        return $this;
    }

    /**
     * @return array
     */
    public function getHeaders(): array
    {
        $cacheRules = implode(', ', $this->cacheRules);
        $timestamp = gmdate('D, d M Y H:i:s \G\M\T');

        return [
            'Last-Modified' => $timestamp,
            'Cache-Control' => "{$cacheRules}, max-age={$this->lifetime}"
        ];
    }

	/**
	 * Will instruct the client and proxy to not cache
	 * the current resource. The resource will be generated
	 * again on the next request.
     *
     * @return $this
	 */
	public function disableCache()
	{
        $this->lifetime = 0;
        $this->cacheRules = [self::NO_CACHE, self::NO_STORE, self::MUST_REVALIDATE];
        return $this;
	}

	/**
	 * @param int $lifetime
	 * @return bool
	 */
	public function isResourceStale(int $lifetime) : bool
	{
		$modified = $_SERVER['HTTP_IF_MODIFIED_SINCE'] ?? null;
		return $modified && strtotime($modified) < time() - $lifetime;
	}
}