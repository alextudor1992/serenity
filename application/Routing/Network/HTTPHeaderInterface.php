<?php

namespace Serenity\Routing\Network;

/**
 * Interface HTTPHeaderInterface
 * @package Serenity\Routing\Network
 */
interface HTTPHeaderInterface
{
    /** @return string[] */
    public function getHeaders(): array;
}