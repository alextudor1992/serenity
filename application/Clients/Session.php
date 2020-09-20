<?php

namespace Serenity\Application\Clients;

use Exception;
use Serenity\Application\App;
use Serenity\Application\Storage\Redis;

/**
 * Class Session
 * @package Serenity\Clients
 */
class Session {
	protected bool $readOnly;
	protected string $sessionId;
	protected Redis $storageAdapter;

    /**
     * Session constructor.
     */
    public function __construct() {
        $sessionConnection = App::config()->get('client.session');
        $this->storageAdapter = new Redis($sessionConnection);
        $this->sessionId = $this->initSession();
    }

    /**
     * @return string
     */
    protected function initSession(): string
    {
        $sessionKey = ini_get('session.name');

        if (empty($_COOKIE[$sessionKey]))
        {
            $sessionParams = session_get_cookie_params();
            $sessionId = $this->generateSessionId();
            $lifetime = $sessionParams['lifetime'];
            $path = $sessionParams['path'];
            $domain = $sessionParams['domain'];
            $secure = $sessionParams['secure'];
            $httponly = $sessionParams['httponly'];

            $lifetime = $lifetime ? (time() + $lifetime) : $lifetime;
            setcookie($sessionKey, $sessionId, $lifetime, $path, $domain, $secure, $httponly);
            return $sessionId;
        }
        return $_COOKIE[$sessionKey];
    }

    /**
     * @return string
     */
    protected function generateSessionId(): string
    {
        try {
            return hash('sha256', random_bytes(128));
        }
        catch (Exception $e) {
            return $this->generateSessionId();
        }
    }

	/**
	 *	Removes all data associated with the current user session
	 */
	public function destroy(): void
	{
        $parameters	= session_get_cookie_params();
        $path = $parameters['path'];
        $domain = $parameters['domain'];
        $secure = $parameters['secure'];
        $httponly = $parameters['httponly'];

        setcookie(ini_get('session.name'), '0', 1, $path, $domain, $secure, $httponly);
	    $this->storageAdapter->remove($this->sessionId);
	}

    /**
     * @param bool $readOnly
     * @return $this
     */
	public function setReadOnlyState(bool $readOnly)
	{
		$this->readOnly = $readOnly;
		return $this;
	}

    /**
     * @param string $key
     * @return string|null
     */
	public function get(string $key): ?string
    {
	    return $this->storageAdapter->tableGet($this->sessionId, $key);
	}

    /**
     * @param string $key
     * @param $value
     * @return bool|int|null
     */
	public function set(string $key, $value)
	{
        return !$this->readOnly ? $this->storageAdapter->tableSet($this->sessionId, $key, $value) : null;
	}

    /**
     * @param string $key
     * @return bool|int|null
     */
	public function remove(string $key)
	{
        return !$this->readOnly ? $this->storageAdapter->tableRemove($this->sessionId, $key) : null;
	}
}