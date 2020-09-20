<?php

namespace Serenity\Application\Events;


/**
 * Class EventSystem
 * @package Serenity
 */
class Event {
	/** @var array */
	protected array $events = [];

	/**
	 * @param string $event
	 * @param null   $data
	 */
	public function emit(string $event, $data=null) : void
	{
		$eventInfo = $this->events[$event] ?? null;

		if ($eventInfo)
		{
			/**
			 * @var Listener $listener
			 */
			foreach ($eventInfo['listeners'] as $listener)
			{
				$listener->onEmit($data);
			}
		}
	}

	/**
	 * @param string   $eventName
	 * @param Listener $listener
	 */
	public function listen(string $eventName, Listener $listener)
	{
		if (!isset($this->events[$eventName]))
		{
			$this->events[$eventName] = ['listeners' => []];
		}
		$this->events[$eventName]['listeners'][] = $listener;
	}

    /**
     * @param string $eventName
     * @param Listener $listener
     */
	public function unlisten(string $eventName, Listener $listener) : void
	{
		$eventInfo = $this->events[$eventName] ?? null;

		if ($eventInfo)
		{
			array_splice($eventInfo['listeners'], array_search($listener, $eventInfo['listeners'], true), 1);
		}
	}
}
