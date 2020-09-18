<?php

namespace Serenity\ServiceDiscovery;

interface ServiceProvider
{
	/** @return string */
	public function getProviderUrl() : string;

	public function getServiceName(string $serviceId) : string;

	public function registerService(string $serviceId) : void;

	public function unregisterService(string $serviceId) : void;
}
