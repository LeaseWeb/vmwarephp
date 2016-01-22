<?php

namespace Vmwarephp\Factory;

use \Vmwarephp\ManagedObject as MainManagedObject;

/**
 * Class SoapMessage
 * @package Vmwarephp\Factory
 */
class SoapMessage
{
    /**
     * @param MainManagedObject $managedObject
     * @param array             $arguments
     *
     * @return array
     */
    public static function makeUsingManagedObject(MainManagedObject $managedObject, $arguments = [])
    {
        $soapMessage['_this'] = $managedObject->toReference();
        foreach ($arguments as $args) {
            $soapMessage = array_merge($soapMessage, $args);
        }
        return $soapMessage;
    }

    /**
     * @return mixed
     */
    public static function makeForServiceInstance()
    {
        $soapMessage['_this'] = new \SoapVar('ServiceInstance', XSD_STRING, 'ServiceInstance');
        return $soapMessage;
    }
}
