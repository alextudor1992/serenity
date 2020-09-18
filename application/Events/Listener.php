<?php

namespace Serenity\Events;

use Serenity\App;

abstract class Listener
{
	/**
	 * @param mixed $data
	 * @return void
	 */
	abstract public function onEmit($data) : void;

	/**
	 * @param string $eventName
	 */
	final public function unlisten(string $eventName)
	{
		App::events()->unlisten($eventName, $this);
	}
}
