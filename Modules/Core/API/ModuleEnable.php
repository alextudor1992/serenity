<?php

namespace Serenity\Modules\Core\API;

use JsonException;
use Serenity\App;
use Serenity\Modules\Exception\InvalidModuleException;
use Serenity\Modules\Exception\ModuleAlreadyEnabledException;
use Serenity\Routing\JSONResponse;
use Serenity\Routing\Resolver;
use Serenity\Routing\Response;
use Serenity\Routing\Request;

/**
 * Class ModuleEnable
 * @package Serenity\Modules\Core\API
 */
class ModuleEnable extends Resolver
{
    /**
     * @inheritDoc
     * @throws JsonException
     */
    public function resolve(Request $request)
    {
        $pluginName = $request->getParam('pluginName');

        try
        {
            App::modules()->enable(App::modules()->findByName($pluginName));
        }
        catch (InvalidModuleException $e)
        {
            return new Response(Response::CODE_NOT_FOUND);
        }
        catch (ModuleAlreadyEnabledException $e)
        {
            return new JSONResponse(Response::CODE_SUCCESS_EMPTY, [$pluginName => true]);
        }
        return new JSONResponse(Response::CODE_SUCCESS_EMPTY, [$pluginName => true]);
    }

    /**
     * @inheritDoc
     */
    public function getMethod(): string
    {
       return Request::METHOD_PUT;
    }

    /**
     * @inheritDoc
     */
    public function getRoute(): string
    {
        return '/$moduleName';
    }

    public function getNamespace(): string
    {
        return '/modules';
    }
}