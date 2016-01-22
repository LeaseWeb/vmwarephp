<?php

namespace Vmwarephp\Factory;

use \Vmwarephp\Vhost;
use \Vmwarephp\Service as MainService;

/**
 * Class Service
 * @package Vmwarephp\Factory
 */
class Service
{
    /**
     * @param Vhost $vhost
     *
     * @return MainService
     */
    public static function make(Vhost $vhost)
    {
        return new MainService($vhost);
    }

    /**
     * @param Vhost $vhost
     *
     * @return MainService
     */
    public static function makeConnected(Vhost $vhost)
    {
        $service = self::make($vhost);
        $service->connect();
        return $service;
    }
}
