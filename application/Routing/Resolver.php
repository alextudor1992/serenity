<?php

namespace Serenity\Application\Routing;

/**
 * Class Resolver
 * @package Serenity\Routing
 */
abstract class Resolver {
	/**
	 * @param Request $request
	 * @return Response|Redirect
     */
    abstract public function resolve(Request $request);

    /**
     * @return string
     */
    abstract public function getMethod() : string;

    /**
     * @return string
     */
    abstract public function getRoute() : string;

    /**
     * @return string
     */
    public function getNamespace() : string
    {
        return '/';
    }

    /**
     * @return string
     */
    final public function getFullPath() : string
    {
        return $this->getNamespace().$this->getRoute();
    }
}
