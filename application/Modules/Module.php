<?php

namespace Serenity\Modules;

/**
 * Class Module
 * @package Serenity\Modules
 */
class Module
{
	protected string $id;
	protected bool $active = false;

	/**
	 * Module constructor.
	 * @param string $moduleId
	 */
	public function __construct(string $moduleId)
	{
		$this->id = $moduleId;
	}

    /**
     * @return string
     */
	final public function getModuleId(): string
    {
		return $this->id;
	}

    /**
     * @return bool
     */
	public function isActive(): bool
	{
		return $this->active;
	}

    /**
     * @param bool $state
     */
	public function setActiveState(bool $state): void
	{
		$this->active = $state;
	}

    /**
     *
     */
	public function onStart() : void {}
}
