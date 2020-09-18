<?php

namespace Serenity\Modules;

use Generator;
use RuntimeException;
use Serenity\App;

class ModulesContainer
{
	/** @var Module[] */
	protected array $modules = [];

    /**
     * ModulesContainer constructor.
     * @param array $modulesList
     */
	public function __construct(array $modulesList)
    {
        $this->startModules($modulesList);
        $modulesStates = App::config()->get('modules');

        foreach ($this->modules as $moduleId => $module)
        {
            $module->setActiveState($modulesStates[$moduleId] ?? true);
        }

        if (!$modulesStates)
        {
            $this->saveModulesState();
        }
    }

	protected function startModules(array $modulesList) : void
	{
		foreach ($modulesList as $moduleId => $moduleClass)
		{
			$module = new $moduleClass($moduleId);

			if (!($module instanceof Module))
			{
				throw new RuntimeException($moduleId.' must be an instance of '.Module::class);
			}

			$this->modules[$moduleId] = $module;
			$module->onStart();
		}
	}

	public function stopModules(): void
    {
        foreach ($this->modules as $module)
        {
            $module->onFinish();
        }
    }

	/**
	 * @return Module[]
	 */
	public function getActiveModules() : array
	{
		$modules = [];

		foreach ($this->modules as $moduleId => $module)
		{
			if ($module->isActive())
			{
				$modules[$moduleId] = $module;
			}
		}
		return $modules;
	}

	public function saveModulesState(): void
    {
        $modules = [];

        foreach ($this->modules as $moduleId => $module)
        {
            if ($module->isActive())
            {
                $modules[$moduleId] = true;
            }
        }
		App::config()->set( 'modules', $modules);
	}

	public function enable(Module $module, bool $temporary=true)
	{
		if ($module->isActive())
		{
			throw new RuntimeException("Module {$module->getModuleId()} is already enabled.");
		}

		$module->setActiveState(true);

		if (!$temporary)
		{
			$this->saveModulesState();
		}
	}

    /**
     * @param Module $module
     * @param bool $temporary
     */
	public function disable(Module $module, bool $temporary=true): void
    {
		if (!$module->isActive())
		{
			throw new RuntimeException("Module {$module->getModuleId()} is already disabled.");
		}

		$module->setActiveState(false);

		if (!$temporary)
		{
			$this->saveModulesState();
		}
	}

	public function findByName(string $pluginName) :? Module
    {
        return $this->modules[$pluginName] ?? null;
    }

    /**
     * @return Generator|null
     */
    public function getIterator(): ?Generator
    {
        foreach ($this->modules as $module)
        {
            yield $module;
        }
    }
}
