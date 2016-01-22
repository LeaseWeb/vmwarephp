<?php
namespace Vmwarephp;

/**
 * Class ManagedObject
 * @package Vmwarephp
 */
class ManagedObject
{
    /** @var \ManagedObjectReference $reference */
    private $reference;

    /** @var Service $vmwareService */
    protected $vmwareService;

    /**
     * ManagedObject constructor.
     *
     * @param Service                 $vmwareService
     * @param \ManagedObjectReference $managedObjectReference
     */
    public function __construct(Service $vmwareService, \ManagedObjectReference $managedObjectReference)
    {
        $this->vmwareService = $vmwareService;
        $this->reference = $managedObjectReference;
    }

    /**
     * @return mixed
     */
    public function getParentHost()
    {
        return $this->vmwareService->getVhostHost();
    }

    /**
     * @param $propertyName
     *
     * @return mixed
     */
    public function __get($propertyName)
    {
        if (!isset($this->$propertyName)) {
            $queryForProperty = 'get' . ucfirst($propertyName);
            return $this->$queryForProperty();
        }
        return $this->$propertyName;
    }

    /**
     * @param $propertyName
     * @param $propertyValue
     */
    public function __set($propertyName, $propertyValue)
    {
        $this->$propertyName = $propertyValue;
    }

    /**
     * @return null
     */
    public function getReferenceType()
    {
        return $this->reference->type;
    }

    /**
     * @return null
     */
    public function getReferenceId()
    {
        return $this->reference->_;
    }

    /**
     * @return \ManagedObjectReference
     */
    public function toReference()
    {
        return $this->reference;
    }

    /**
     * @param $method
     * @param $arguments
     *
     * @return mixed
     */
    public function __call($method, $arguments)
    {
        return $this->vmwareService->$method($this, $arguments);
    }

    /**
     * @param ManagedObject $managedObject
     *
     * @return bool
     */
    public function equals(ManagedObject $managedObject)
    {
        return ($this->toReference() == $managedObject->toReference() &&
            $this->getParentHost() == $managedObject->getParentHost());
    }
}
