<?php

namespace Serenity\Storage;

use Aws\Result;
use Aws\S3\S3Client;
use InvalidArgumentException;
use RuntimeException;

/**
 * Class ObjectStorage
 * @package Serenity
 */
class ObjectStorage
{
	protected S3Client $adapter;
	protected array $credentials;
	protected string $connectionName;

	public function __construct(string $connectionName)
	{
	    $connectionsList = App::config()->get('object_storage');

	    if (!empty($connectionsList[$connectionName]))
        {
            $this->connectionName = $connectionName;
            $this->credentials = $connectionsList[$connectionName];
        }
	    else throw new RuntimeException("Invalid object storage connection '{$connectionName}'");
	}

	/**
	 * @return S3Client|null
	 */
	protected function getAdapter() : S3Client
	{
		$adapter = $this->adapter;

		if (!$adapter)
		{
            $adapter = $this->adapter = new S3Client($this->credentials);
		}
        return $adapter;
	}

	/**
	 * @param string $bucket
	 * @param string $key
	 * @return mixed|null
	 */
	public function get(string $bucket, string $key)
	{
        $result	= $this->getAdapter()->getObject(['Key' => $key, 'Bucket' => $bucket]);

        if ($this->hasSucceeded($result))
        {
            return $result->get('data');
        }
		return null;
	}

	public function set(string $bucket, string $key, string $data): bool
	{
        return $this->hasSucceeded($this->getAdapter()->putObject(['Key' => $key, 'Bucket' => $bucket, 'Body' => $data])->get('Status'));
	}

	public function remove(string $bucket, string $key) : bool
	{
		return $this->hasSucceeded($this->getAdapter()->deleteObject(['Key' => $key, 'Bucket' => $bucket]));
	}

    /**
     * @param string $bucket
     * @return string|null
     */
	public function list(string $bucket): ?string
    {
        $result	= $this->getAdapter()->listObjects(['Bucket' => $bucket]);
        return $this->hasSucceeded($result) ? $result->get('data') : null;
	}

	/**
	 * @param Result $result
	 * @return bool
	 */
	protected function hasSucceeded(Result $result) : bool
	{
		return $result->get('Status') < 400;
	}
}