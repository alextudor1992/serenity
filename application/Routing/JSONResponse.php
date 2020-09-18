<?php

namespace Serenity\Routing;

use JsonException;

/**
 * Class JSONResponse
 * @package Serenity\Routing
 */
class JSONResponse extends Response
{
    /**
     * JSONResponse constructor.
     * @param int $code
     * @param mixed $data
     * @throws JsonException
     */
    public function __construct(int $code, $data = null)
    {
        parent::__construct($code, json_encode($data, JSON_THROW_ON_ERROR), Response::TYPE_JSON);
    }
}