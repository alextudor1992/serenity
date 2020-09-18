<?php

namespace Serenity\ServiceDiscovery;

interface ServiceProvider
{
	/**
     * @return string
     */
	public function getProviderUrl() : string;

    /**
     * @param string $serviceId
     * @return string
     */
	public function getServiceName(string $serviceId) : string;

    /**
     * @param string $serviceId
     */
	public function registerService(string $serviceId) : void;

    /**
     * @param string $serviceId
     */
	public function unregisterService(string $serviceId) : void;
}
