<?php

namespace Serenity\Storage;

use Redis as RedisAdapter;

use RuntimeException;
use Serenity\App;

/**
 * Class Redis
 * @package Serenity\Storage
 */
class Redis
{
	public const READ_NODE		= 'slave';
	public const WRITE_NODE		= 'master';
	public const SENTINEL_NODE	= 'sentinel';

	/** @var RedisAdapter[] */
	protected array $connections;
    protected array $credentials;
	protected string $connectionName;

    /**
     * Redis constructor.
     * @param string $connectionName
     */
	public function __construct(string $connectionName)
	{
        $redisConnections = App::config()->get('redis');

        if (!empty($redisConnections[$connectionName]))
        {
            $this->credentials = $redisConnections[$connectionName];
            $this->connectionName = $connectionName;
        }
        else {
            throw new RuntimeException("Invalid configuration for Redis or connection '$connectionName' does not exist.");
        }
	}

    /**
     * @param string $nodeType
     */
	public function disconnect(string $nodeType): void
	{
		$connection = $this->connections[$nodeType] ?? null;

		if ($connection)
		{
			$connection->close();
            unset($this->connections[$nodeType]);
		}
	}

	/**
	 * @param string $key
	 * @return bool|string
	 */
	public function get(string $key)
	{
		return $this->getConnection(static::READ_NODE)->get($key);
	}

	/**
	 * @param array $keys
	 * @return array
	 */
	public function getBatch(array $keys): array
	{
		return $this->getConnection(static::READ_NODE)->mget($keys);
	}

	/**
	 * Sets data at a given key on a Redis server.
	 *
	 * @param string $key
	 * @param $value
	 * @param int $ttl
	 * @return bool
	 */
	public function set(string $key, $value, int $ttl=0) : bool
	{
        return $this->getConnection(static::WRITE_NODE)->set($key, $value, $ttl > 0 ? ['ex' => $ttl] : null);
	}

	/**
	 * Removes the data at a given key from the Redis server.
	 *
	 * @param string $key
	 * @return bool
	 */
	public function remove(string $key) : bool
	{
		return $this->getConnection(static::WRITE_NODE)->del($key);
	}

	/**
	 * @param string $topic
	 * @param string $value
	 * @return int
	 */
	public function publish(string $topic, string $value) : int
	{
		return $this->getConnection(static::WRITE_NODE)->publish($topic, $value);
	}

	/**
	 * Requests a Sentinel to provide any available Master node.
	 * @return string
	 */
	protected function getAvailableMaster() : string
	{
		return $this->getConnection(static::SENTINEL_NODE)->rawCommand('SENTINEL', ['get-master-addr-by-name', $this->connectionName]);
	}

	/**
	 * @param string $tableKey
	 * @param string $field
	 * @param $value
	 * @return bool|int
	 */
	public function tableSet(string $tableKey, string $field, $value)
	{
		return $this->getConnection(static::WRITE_NODE)->hSet($tableKey, $field, $value);
	}

	/**
	 * @param string $tableKey
	 * @param string $field
	 * @return string
	 */
	public function tableGet(string $tableKey, string $field): string
	{
		return $this->getConnection(static::READ_NODE)->hGet($tableKey, $field);
	}

	/**
	 * @param string $tableKey
	 * @param array $fields
	 * @return array
	 */
	public function tableGetMultiple(string $tableKey, array $fields): array
	{
		return $this->getConnection(static::READ_NODE)->hMGet($tableKey, $fields);
	}

	/**
	 * @param string $tableKey
	 * @param array $fields
	 * @return bool
	 */
	public function tableSetMultiple(string $tableKey, array $fields): bool
	{
		return $this->getConnection(static::WRITE_NODE)->hMSet($tableKey, $fields);
	}

	/**
	 * @param string $tableKey
	 * @param array $fields
	 * @return array
	 */
	public function tableRemoveMultiple(string $tableKey, array $fields): array
	{
		$transaction = $this->getConnection(static::WRITE_NODE)->multi(RedisAdapter::PIPELINE);

		foreach ($fields as $field)
		{
			$transaction->hDel($tableKey, $field);
		}
		return $transaction->exec();
	}

	/**
	 * @param string $tableKey
	 * @return array
	 */
	public function tableGetAll(string $tableKey): array
	{
		return $this->getConnection(static::READ_NODE)->hGetAll($tableKey);
	}

	/**
	 * @param string $tableKey
	 * @param string $field
	 * @return bool|int
	 */
	public function tableRemove(string $tableKey, string $field)
	{
		return $this->getConnection(static::WRITE_NODE)->hDel($tableKey, $field);
	}

	public function startTransaction()
	{
		$this->getConnection(static::WRITE_NODE)->multi();
		return $this;
	}

    /**
     * @return $this
     */
	public function commitTransaction()
	{
		$this->getConnection(static::WRITE_NODE)->exec();
		return $this;
	}

    /**
     * @return $this
     */
	public function discardTransaction()
	{
		$this->getConnection(static::WRITE_NODE)->discard();
		return $this;
	}

    /**
     * @param string $nodeType
     * @return RedisAdapter
     */
	protected function getConnection(string $nodeType) : RedisAdapter
	{
		if (!isset($this->connections[$nodeType]))
		{
            $this->connections[$nodeType] = $this->connect($nodeType);
		}
		return $this->connections[$nodeType];
	}

    /**
     * @param string $nodeType
     * @return RedisAdapter
     */
    protected function connect(string $nodeType) : RedisAdapter
    {
        $credentials = $this->credentials[$nodeType] ?? null;

        if (!empty($credentials) && isset($credentials['host'], $credentials['port']))
        {
            $redisAdapter = new RedisAdapter();

            if ($redisAdapter->pconnect($credentials['host'], $credentials['port'], $credentials['timeout'] ?? 10.0))
            {
                if ($nodeType !== static::SENTINEL_NODE)
                {
                    $redisAdapter->setOption(RedisAdapter::OPT_SERIALIZER, RedisAdapter::SERIALIZER_IGBINARY);
                }

                $this->connections[$nodeType] = $redisAdapter;
                return $redisAdapter;
            }
            throw new RuntimeException("Redis connection to {$this->connectionName}:{$nodeType} couldn't be established.");
        }
        throw new RuntimeException("Credentials for Redis connection {$this->connectionName}:{$nodeType} are invalid.");
    }
}