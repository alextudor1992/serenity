<?php

namespace Serenity\Modules;

use Serenity\ServiceDiscovery\ServiceProvider;

/**
 * Class Service
 * @package Serenity\Modules
 */
class Service extends Module
{
    protected ServiceProvider $serviceProvider;

    /**
     * Service constructor.
     * @param string $name
     * @param ServiceProvider $serviceProvider
     */
	public function __construct(string $name, ServiceProvider $serviceProvider)
	{
		parent::__construct($name);
		$this->serviceProvider = $serviceProvider;
	}

    /**
     * @param bool $state
     */
	public function setActiveState(bool $state): void
	{
		if ($state)
		{
			$this->serviceProvider->unregisterService($this->getModuleId());
		}
		else $this->serviceProvider->registerService($this->getModuleId());

		parent::setActiveState($state);
	}
}
