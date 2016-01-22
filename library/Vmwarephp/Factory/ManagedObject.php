<?php
namespace Vmwarephp\Factory;

use \Vmwarephp\WsdlClassMapper;
use \Vmwarephp\Service as MainService;

/**
 * Class ManagedObject
 * @package Vmwarephp\Factory
 */
class ManagedObject
{
    private $wsdlClassMapper;

    /**
     * ManagedObject constructor.
     *
     * @param WsdlClassMapper|null $classMapper
     */
    public function __construct(WsdlClassMapper $classMapper = null)
    {
        $this->wsdlClassMapper = $classMapper ?: new WsdlClassMapper;
    }

    /**
     * @param MainService                 $service
     * @param \ManagedObjectReference $reference
     *
     * @return \Vmwarephp\ManagedObject
     */
    public function make(MainService $service, \ManagedObjectReference $reference)
    {
        $classMap = $this->wsdlClassMapper->getClassMap();
        if (array_key_exists($reference->type, $classMap)) {
            $className = $classMap[$reference->type];
            return new $className($service, $reference);
        }
        return new \Vmwarephp\ManagedObject($service, $reference);
    }

    /**
     * @param MainService $service
     * @param             $id
     * @param             $type
     *
     * @return \Vmwarephp\ManagedObject
     */
    public function makeGeneratingReference(MainService $service, $id, $type)
    {
        return $this->make($service, $this->makeReference($id, $type));
    }

    /**
     * @param $id
     * @param $type
     *
     * @return \ManagedObjectReference
     */
    public function makeReference($id, $type)
    {
        return new \ManagedObjectReference($id, $type);
    }
}
