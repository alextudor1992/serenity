<?php

namespace Serenity\Modules\Core\API;

use JsonException;
use Serenity\App;
use Serenity\Routing\JSONResponse;
use Serenity\Routing\Request;
use Serenity\Routing\Resolver;
use Serenity\Routing\Response;


/**
 * Class ModulesList
 * @package Serenity\Modules\Core\API
 */
class ModulesList extends Resolver
{
    /**
     * @inheritDoc
     * @throws JsonException
     */
    public function resolve(Request $request)
    {
        $activeModules = App::modules()->getActiveModules();
        return new JSONResponse(Response::CODE_SUCCESS, array_keys($activeModules));
    }

    /**
     * @inheritDoc
     */
    public function getMethod(): string
    {
        return Request::METHOD_GET;
    }

    /**
     * @inheritDoc
     */
    public function getRoute(): string
    {
        return '';
    }

    public function getNamespace(): string
    {
        return '/modules';
    }
}