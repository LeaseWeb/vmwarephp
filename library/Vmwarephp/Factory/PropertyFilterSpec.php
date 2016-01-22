<?php

namespace Vmwarephp\Factory;

use \Vmwarephp\Service as MainService;
use \Vmwarephp\Exception\InvalidTraversalPropertyFormat;

/**
 * Class PropertyFilterSpec
 * @package Vmwarephp\Factory
 */
class PropertyFilterSpec
{
    /**
     * @param $managedObjectType
     * @param $referenceId
     * @param $propertiesToCollect
     *
     * @return \PropertyFilterSpec
     */
    public function makeForOneManagedObject($managedObjectType, $referenceId, $propertiesToCollect)
    {
        $sets = $this->makeTraversalAndPropSets($managedObjectType, $propertiesToCollect);
        return new \PropertyFilterSpec(
            $sets['propSet'],
            $this->makeObjectSet($managedObjectType, $referenceId, false, $sets['traversalSet'])
        );
    }

    /**
     * @param             $managedObjectType
     * @param             $propertiesToCollect
     * @param MainService $service
     *
     * @return \PropertyFilterSpec
     */
    public function makeForTraversingAllInventory($managedObjectType, $propertiesToCollect, MainService $service)
    {
        $containerView = $this->makeContainerView($managedObjectType, $service);
        $sets = $this->makeTraversalAndPropSets($managedObjectType, $propertiesToCollect);
        $traversalSpec = $this->makeTraversalSpec('view', 'ContainerView');
        $traversalSpec->selectSet = $sets['traversalSet'];
        return new \PropertyFilterSpec(
            $sets['propSet'],
            $this->makeObjectSet($containerView->type, $containerView->_, true, $traversalSpec)
        );
    }

    /**
     * @param $managedObjectType
     * @param $propertiesToCollect
     *
     * @return array
     * @throws \Vmwarephp\Exception\InvalidTraversalPropertyFormat
     */
    private function makeTraversalAndPropSets($managedObjectType, $propertiesToCollect)
    {
        $specs = ['propSet' => [], 'traversalSet' => []];
        if ($propertiesToCollect == 'all') {
            $specs['propSet'][] = $this->makePropSpec($managedObjectType, $propertiesToCollect);
            return $specs;
        }
        $nonTraversalProperties = [];
        foreach ($propertiesToCollect as $key => $value) {
            if ($this->isATraversalProperty($key)) {
                $this->checkTraversalPropertyFormat($propertiesToCollect, $key);
                $specs['traversalSet'][] = $this->makeTraversalSpec($key, $managedObjectType);
                $specs['propSet'][] = $this->makePropSpec($propertiesToCollect[$key][0], $propertiesToCollect[$key][1]);
            } else {
                $nonTraversalProperties[] = $value;
            }
        }
        $specs['propSet'][] = $this->makePropSpec($managedObjectType, $nonTraversalProperties);
        return $specs;
    }

    /**
     * @param $managedObjectType
     * @param $vmwareService
     *
     * @return mixed
     */
    private function makeContainerView($managedObjectType, $vmwareService)
    {
        $viewManager = $vmwareService->getViewManager();
        $containerView = $viewManager->CreateContainerView(
            [
                'container' => $vmwareService->getRootFolder()->toReference(),
                'recursive' => true,
                'type' => $managedObjectType
            ]
        );
        return $containerView->toReference();
    }

    /**
     * @param $key
     * @param $managedObjectType
     *
     * @return \TraversalSpec
     */
    private function makeTraversalSpec($key, $managedObjectType)
    {
        return new \TraversalSpec('traverse' . ucfirst($key), $managedObjectType, $key, false, null);
    }

    /**
     * @param $managedObjectType
     * @param $propertiesToBeRetrieved
     *
     * @return \PropertySpec
     */
    private function makePropSpec($managedObjectType, $propertiesToBeRetrieved)
    {
        if ($propertiesToBeRetrieved == 'all') {
            return new \PropertySpec($managedObjectType, true);
        }
        return new \PropertySpec($managedObjectType, false, $propertiesToBeRetrieved);
    }

    /**
     * @param $key
     *
     * @return bool
     */
    private function isATraversalProperty($key)
    {
        return !is_numeric($key);
    }

    /**
     * @param $managedObjectType
     * @param $referenceId
     * @param $skip
     * @param $traversalSpecsSelectSet
     *
     * @return array
     */
    private function makeObjectSet($managedObjectType, $referenceId, $skip, $traversalSpecsSelectSet)
    {
        return [
            new \ObjectSpec(
                new \ManagedObjectReference($referenceId, $managedObjectType),
                $skip,
                empty($traversalSpecsSelectSet) ? null : $traversalSpecsSelectSet
            )
        ];
    }

    /**
     * @param $propertiesToCollect
     * @param $key
     *
     * @throws \Vmwarephp\Exception\InvalidTraversalPropertyFormat
     */
    private function checkTraversalPropertyFormat($propertiesToCollect, $key)
    {
        if (!isset($propertiesToCollect[$key][0]) || !isset($propertiesToCollect[$key][1])) {
            throw new InvalidTraversalPropertyFormat();
        }
    }
}
