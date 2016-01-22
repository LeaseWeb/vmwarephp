<?php

namespace Vmwarephp;

use Vmwarephp\Exception as Ex;
use \Vmwarephp\Factory\Service as FactoryService;

/**
 * Class Vhost
 * @package Vmwarephp
 */
class Vhost
{
    /** @var Service $service */
    private $service;

    /**
     * Vhost constructor.
     *
     * @param $host
     * @param $username
     * @param $password
     */
    public function __construct($host, $username, $password)
    {
        $this->host = $host;
        $this->username = $username;
        $this->password = $password;
        $this->initializeService();
    }

    /**
     * @return string
     */
    public function getPort()
    {
        $port = parse_url($this->host, PHP_URL_PORT);
        return $port ?: '443';
    }

    /**
     * @param $propertyName
     *
     * @return mixed
     */
    public function __get($propertyName)
    {
        if (!isset($this->$propertyName)) {
            throw new \InvalidArgumentException('Property ' . $propertyName . ' not set on this object!');
        }
        return $this->$propertyName;
    }

    /**
     * @param $propertyName
     * @param $value
     *
     * @throws Ex\InvalidVhost
     */
    public function __set($propertyName, $value)
    {
        $this->validateProperty($propertyName, $value);
        $this->$propertyName = $value;
    }

    /**
     * @return Service
     */
    public function getService()
    {
        return $this->service;
    }

    /**
     * @return mixed
     */
    public function getApiType()
    {
        return $this->getServiceContent()->about->apiType;
    }

    /**
     * @param Service $service
     */
    public function changeService(Service $service)
    {
        $this->service = $service;
    }

    /**
     * Initializes vmware service
     */
    private function initializeService()
    {
        if (!$this->service) {
            $this->service = FactoryService::makeConnected($this);
        }
    }

    /**
     * @param $propertyName
     * @param $value
     *
     * @throws Ex\InvalidVhost
     */
    private function validateProperty($propertyName, $value)
    {
        if (in_array($propertyName, ['host', 'username', 'password']) && empty($value)) {
            throw new Ex\InvalidVhost('Vhost ' . ucfirst($propertyName) . ' cannot be empty!');
        }
    }
}
