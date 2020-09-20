<?php

namespace Serenity\Application\ServiceDiscovery;

interface ServiceProvider {
	/**
	 * @param string $serviceId
	 * @return string
	 */
	public function getServiceName(string $serviceId): string;

	/**
	 * @param string $serviceId
	 */
	public function registerService(string $serviceId): void;

	/**
	 * @param string $serviceId
	 */
	public function unregisterService(string $serviceId): void;

	/**
	 * @param string $serviceId
	 * @return bool
	 */
	public function isServiceHealthy(string $serviceId): bool;
}
