<?php
namespace Vmwarephp\Extensions;

use \Vmwarephp\ManagedObject;
use \Vmwarephp\Service;
use \Vmwarephp\Factory\PropertyFilterSpec;

/**
 * Class PropertyCollector
 * @package Vmwarephp\Extensions
 */
class PropertyCollector extends ManagedObject
{
    private $propFilterSpecFactory;

    /**
     * PropertyCollector constructor.
     *
     * @param Service                 $vmwareService
     * @param \ManagedObjectReference $managedObjectReference
     * @param PropertyFilterSpec|null $factory
     */
    public function __construct(
        Service $vmwareService,
        \ManagedObjectReference $managedObjectReference,
        PropertyFilterSpec $factory = null
    ) {
        parent::__construct($vmwareService, $managedObjectReference);
        $this->propFilterSpecFactory = $factory ?: new PropertyFilterSpec();
    }

    /**
     * @param $managedObjectType
     * @param $propertiesToCollect
     *
     * @return array
     */
    public function collectAll($managedObjectType, $propertiesToCollect)
    {
        $propertyFilterSpec = $this->propFilterSpecFactory->makeForTraversingAllInventory(
            $managedObjectType,
            $propertiesToCollect,
            $this->vmwareService
        );
        $managedObjects = $this->getPropertiesUsingSpec($propertyFilterSpec);
        if (!$managedObjects) {
            return [];
        }
        return is_array($managedObjects) ? $managedObjects : [$managedObjects];
    }

    /**
     * @param $managedObjectType
     * @param $referenceId
     * @param $propertiesToCollect
     *
     * @return null
     */
    public function collectPropertiesFor($managedObjectType, $referenceId, $propertiesToCollect)
    {
        $propertyFilterSpec = $this->propFilterSpecFactory->makeForOneManagedObject(
            $managedObjectType,
            $referenceId,
            $propertiesToCollect
        );
        $result = $this->getPropertiesUsingSpec($propertyFilterSpec);
        return $this->appendTraversedPropertiesToRequestedObject($result, $propertiesToCollect, $managedObjectType);
    }

    /**
     * @param       $propertyFilterSpec
     * @param array $options
     *
     * @return mixed
     */
    private function getPropertiesUsingSpec($propertyFilterSpec, $options = [])
    {
        return $this->RetrieveProperties(['specSet' => $propertyFilterSpec, 'options' => $options]);
    }

    /**
     * @param $collectionResult
     * @param $propertiesToCollect
     * @param $managedObjectType
     *
     * @return null
     * @throws \Exception
     */
    private function appendTraversedPropertiesToRequestedObject(
        $collectionResult,
        $propertiesToCollect,
        $managedObjectType
    ) {
        if (!$collectionResult) {
            return null;
        }
        $hashedCollectionResult = $this->collectionResultToHash($collectionResult);
        $requestedObject = $this->findRequestedObjectInCollectionResult($collectionResult, $managedObjectType);
        $propertiesToCollect = is_array($propertiesToCollect) ? $propertiesToCollect : array($propertiesToCollect);
        foreach ($propertiesToCollect as $key => $value) {
            if ($this->isATraversalProperty($key)) {
                $requestedObject->$key = isset($hashedCollectionResult[$value[0]]) ?
                    $hashedCollectionResult[$value[0]] :
                    null;
            }
        }
        return $requestedObject;
    }

    /**
     * @param $collectionResult
     * @param $managedObjectType
     *
     * @return mixed
     * @throws \Exception
     */
    private function findRequestedObjectInCollectionResult($collectionResult, $managedObjectType)
    {
        if (is_object($collectionResult)) {
            return $collectionResult;
        }
        foreach ($collectionResult as $managedObject) {
            if (strpos(get_class($managedObject), $managedObjectType) !== false ||
                $managedObject->reference->type === $managedObjectType
            ) {
                return $managedObject;
            }
        }
        throw new \Exception('Cannot find the object we requested to collect the properties for in servers response!');
    }

    /**
     * @param $collectionResult
     *
     * @return array
     */
    private function collectionResultToHash($collectionResult)
    {
        $hash = [];
        if (!is_array($collectionResult)) {
            return $hash;
        }
        foreach ($collectionResult as $managedObject) {
            $hash[$managedObject->getReferenceType()][] = $managedObject;
        }
        return $hash;
    }

    /**
     * @param $propertyKey
     *
     * @return bool
     */
    private function isATraversalProperty($propertyKey)
    {
        return !is_numeric($propertyKey);
    }
}
