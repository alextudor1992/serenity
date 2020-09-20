<?php

namespace Serenity\GraphQL;


use GraphQL\Type\Definition\ObjectType;

/**
 * Class AbstractType
 * @package Serenity\GraphQL
 */
abstract class AbstractType extends ObjectType {
	/**
	 * AbstractType constructor.
	 */
	final public function __construct() {
		parent::__construct($this->getSchema());
	}

	/**
	 * @return array
	 */
	abstract protected function getSchema(): array;
}