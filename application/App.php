<?php

namespace Serenity\Application;

use RuntimeException;
use Serenity\Application\Clients\Session;
use Serenity\Application\Events\CoreEvent;
use Serenity\Application\Events\Event;
use Serenity\Application\Modules\ModulesContainer;
use Serenity\Application\Routing\Router;
use function register_shutdown_function;

/**
 * Class App
 * @package Serenity
 */
final class App {
	public const APP_STATE_NOT_INITIALIZED = 0;
	public const APP_STATE_INITIALIZING = 1;
	public const APP_STATE_INITIALIZED = 2;
	public const APP_STATE_SHUTTING_DOWN = 3;

    protected static int $appState = self::APP_STATE_NOT_INITIALIZED;

    /**
     * @param array $modulesList
     */
	public static function init(array $modulesList): void
	{
		if (static::getAppState() !== self::APP_STATE_NOT_INITIALIZED)
		{
			throw new RuntimeException('App is already initialized.');
		}

		static::setAppState(self::APP_STATE_INITIALIZING);
		static::modules($modulesList);
		static::router();

		register_shutdown_function(static function()
		{
			static::setAppState(self::APP_STATE_SHUTTING_DOWN);
			static::events()->emit(CoreEvent::REQUEST_END);
			static::modules()->stopModules();
		});

		static::setAppState(self::APP_STATE_INITIALIZED);
	}

	public static function getAppState() : int
	{
		return static::$appState;
	}

	protected static function setAppState(int $state) : void
	{
		static::$appState = $state;
	}

	public static function name() : string
	{
		static $name;

		if (!$name)
		{
			$name = static::config()->get('appName');
		}
		return $name;
	}

	/**
	 * @return Configuration
	 */
	public static function config() : Configuration
	{
		static $config = null;

		if (!$config)
		{
			$config = new Configuration();
		}
		return $config;
	}

	public static function modules(array $modulesList=null) : ModulesContainer
	{
		static $modulesContainer = null;

		if (!$modulesContainer)
		{
			$modulesContainer = new ModulesContainer($modulesList);
		}
		return $modulesContainer;
	}

	/**
	 * @return Router
	 */
	public static function router() : Router
	{
		static $router = null;

		if (!$router)
		{
			$router = new Router();
		}
		return $router;
	}

	public static function events() : Event
	{
		static $event = null;

		if (!$event)
		{
			$event = new Event();
		}
		return $event;
	}

	public static function session() : Session
    {
        static $session = null;

        if (!$session)
        {
            $session = new Session();
        }
        return $session;
    }
}
