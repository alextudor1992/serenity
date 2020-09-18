<?php

namespace Serenity\Modules\Core;


use Serenity\App;
use Serenity\Modules\Core\API\ModuleDisable;
use Serenity\Modules\Core\API\ModuleEnable;
use Serenity\Modules\Core\API\ModulesList;
use Serenity\Modules\Service;

class ServiceAPI extends Service
{
    public function isActive(): bool
    {
        return true;
    }

    public function setActiveState(bool $state): void {}

    /**
     * @inheritDoc
     */
    public function onStart(): void
    {
        App::router()
            ->addResolver($this, new ModuleEnable())
            ->addResolver($this, new ModuleDisable())
            ->addResolver($this, new ModulesList());
    }
}