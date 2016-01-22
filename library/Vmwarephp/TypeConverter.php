<?php

namespace Vmwarephp;

use \Vmwarephp\Factory\ManagedObject;

/**
 * Class TypeConverter
 * @package Vmwarephp
 */
class TypeConverter
{
    private $vmwareService;
    private $managedObjectFactory;

    /**
     * TypeConverter constructor.
     *
     * @param Service                    $service
     * @param ManagedObject|null $managedObjectFactory
     */
    public function __construct(Service $service, ManagedObject $managedObjectFactory = null)
    {
        $this->vmwareService = $service;
        $this->managedObjectFactory = $managedObjectFactory ?: new ManagedObject();
    }

    /**
     * @param $value
     *
     * @return null|ManagedObject
     */
    public function convert($value)
    {
        if ($this->isANullValue($value)) {
            return null;
        }
        if ($this->isABasicPrimitiveType($value)) {
            return $value;
        }
        if ($this->isAManagedObjectReference($value)) {
            return $this->managedObjectFactory->make($this->vmwareService, $value);
        }
        if ($this->isAnObjectContent($value)) {
            return $this->convertObjectContent($value);
        }
        if (is_array($value)) {
            return $this->convertEachValueOfTheArray($value);
        }
        if ($this->isAnArrayOf($value)) {
            $elementType = $this->getArrayOfElementType($value);
            return $this->convert(is_array($value->$elementType) ? $value->$elementType : [$value->$elementType]);
        }
        return $this->convertDataObject($value);
    }

    /**
     * @param $objectContent
     *
     * @return null|ManagedObject
     */
    private function convertObjectContent($objectContent)
    {
        $managedObject = $this->convert($objectContent->obj);
        $properties = $objectContent->propSet;
        if (!$properties) {
            return $managedObject;
        }
        $this->addPropertiesToObject($managedObject, is_array($properties) ? $properties : [$properties]);
        return $managedObject;
    }

    /**
     * @param $managedObject
     * @param $properties
     */
    private function addPropertiesToObject($managedObject, $properties)
    {
        foreach ($properties as $dynamicProperty) {
            $propertyName = $dynamicProperty->name;
            $managedObject->$propertyName = $this->convert($dynamicProperty->val);
        }
    }

    /**
     * @param $value
     *
     * @return mixed
     */
    private function convertEachValueOfTheArray($value)
    {
        foreach ($value as $key => $val) {
            $value[$key] = $this->convert($val);
        }
        return $value;
    }

    /**
     * @param $value
     *
     * @return mixed
     */
    private function convertDataObject($value)
    {
        $objProperties = get_object_vars($value);
        foreach ($objProperties as $propertyName => $propertyValue) {
            $value->$propertyName = $this->convert($propertyValue);
        }
        return $value;
    }

    /**
     * @param $value
     *
     * @return bool
     */
    private function isAnObjectContent($value)
    {
        return $value instanceof \ObjectContent;
    }

    /**
     * @param $value
     *
     * @return bool
     */
    private function isANullValue($value)
    {
        if (is_null($value)) {
            return true;
        }
        if (!is_object($value)) {
            return false;
        }
        $objProperties = get_object_vars($value);
        return count($objProperties) == 0;
    }

    /**
     * @param $value
     *
     * @return bool
     */
    private function isABasicPrimitiveType($value)
    {
        return is_string($value) || is_double($value) || is_float($value) || is_int($value) || is_bool($value);
    }

    /**
     * @param $value
     *
     * @return bool
     */
    private function isAManagedObjectReference($value)
    {
        if (!is_object($value)) {
            return false;
        }
        return $value instanceof \ManagedObjectReference || (isset($value->_) && isset($value->type));
    }

    /**
     * @param $value
     *
     * @return bool
     */
    private function isAnArrayOf($value)
    {
        if (!is_object($value)) {
            return false;
        }
        $objectClass = get_class($value);
        return preg_match('/ArrayOf/', $objectClass) ? true : false;
    }

    /**
     * @param $value
     *
     * @return mixed
     */
    private function getArrayOfElementType($value)
    {
        $objVars = get_object_vars($value);
        $objVarsKeys = array_keys($objVars);
        return end($objVarsKeys);
    }
}
