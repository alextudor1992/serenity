<?php

namespace Serenity;

/**
 * Class Configuration
 * @package Serenity
 */
class Configuration
{
    protected array $data = [];

	/**
	 * @param string $key
	 * @return mixed|null
	 */
	public function get(string $key)
	{
		$data = $this->data[$key] ?? null;

		if (!$data)
		{
			$data = apcu_fetch($key, $success);

			if ($success)
			{
				$this->data[$key] = $data;
			}
		}
		return $data;
	}

	public function getMultiple(array $keys) : array
	{
		$data = apcu_fetch($keys, $success);

		if ($success)
		{
			foreach ($data as $key => $value)
			{
				$this->data[$key] = $value;
			}
		}
		return $data;
	}

	/**
	 * @param string $key
	 * @param        $value
	 * @param bool   $persistent
	 * @param int    $lifetime
	 * @param bool   $overwrite
	 */
	public function set(string $key, $value, bool $persistent=false, int $lifetime=0, bool $overwrite=true) : void
	{
		if ($persistent)
		{
			if ($overwrite)
			{
				apcu_store($key, $value, $lifetime);
			}
			else apcu_add($key, $value, $lifetime);
		}

		if ($overwrite || !isset($this->data[$key]))
		{
			$this->data[$key] = $value;
		}
	}

	public function setMultiple(array $data, bool $persistent=false, int $lifetime=0, bool $overwrite=true) : void
	{
		if ($persistent)
		{
			if ($overwrite)
			{
				apcu_store($data, $lifetime);
			}
			else apcu_add($data, $lifetime);
		}

		foreach ($data as $key => $value)
		{
			if ($overwrite || !isset($this->data[$key]))
			{
				$this->data[$key] = $value;
			}
		}
	}
}
