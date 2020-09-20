<?php

namespace Serenity\Application\ServiceDiscovery\Providers;

use Aws\Result;
use Aws\ServiceDiscovery\ServiceDiscoveryClient;
use Serenity\Application\ServiceDiscovery\ServiceProvider;

/**
 * Class AWSServiceProvider
 * @package Serenity\ServiceDiscovery\Providers
 */
class AWSServiceProvider implements ServiceProvider {
	protected ServiceDiscoveryClient $awsClient;

	public function __construct() {
		$this->awsClient = new ServiceDiscoveryClient([]);
	}

	/**
	 * @inheritDoc
	 */
	public function getServiceName(string $serviceId): string {
		$result = $this->awsClient->getService([]);

		if ($this->hasSucceeded($result)) {
			return (string)$result->get('data');
		}
		return $serviceId;
	}

	/**
	 * @inheritDoc
	 */
	public function registerService(string $serviceId): void {
		$this->awsClient->registerInstanceAsync([]);
	}

	/**
	 * @inheritDoc
	 */
	public function unregisterService(string $serviceId): void {
		$this->awsClient->deregisterInstanceAsync([]);
	}

	/**
	 * @inheritDoc
	 */
	public function isServiceHealthy(string $serviceId): bool {
		$result = $this->awsClient->getInstancesHealthStatus([/** TODO: Add args */]);

		if ($this->hasSucceeded($result)) {
			return (bool)$result->get('data');
		}
		return false;
	}

	/**
	 * @param Result $result
	 * @return bool
	 */
	protected function hasSucceeded(Result $result): bool {
		return $result->get('Status') < 400;
	}
}