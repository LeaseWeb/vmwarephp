<?php

namespace Vmwarephp;

use \Vmwarephp\Factory\SoapClient;
use \Vmwarephp\Factory\SoapMessage;
use \Vmwarephp\Exception\Soap as SoapException;

/**
 * Class Service
 * @package Vmwarephp
 * @method Extensions\PropertyCollector getPropertyCollector()
 */
class Service
{
    private $soapClient;
    private $vhost;
    private $typeConverter;
    private $serviceContent;
    private $session;
    private $clientFactory;

    /**
     * Service constructor.
     *
     * @param Vhost           $vhost
     * @param SoapClient|null $soapClientFactory
     */
    public function __construct(Vhost $vhost, SoapClient $soapClientFactory = null)
    {
        $this->vhost = $vhost;
        $this->clientFactory = $soapClientFactory ?: new SoapClient();
        $this->soapClient = $this->clientFactory->make($this->vhost);
        $this->typeConverter = new TypeConverter($this);
    }

    /**
     * @param $method
     * @param $arguments
     *
     * @return null
     * @throws Exception\Soap
     */
    public function __call($method, $arguments)
    {
        if ($this->isMethodAPropertyRetrieval($method)) {
            return $this->getQueriedProperty($method, $arguments);
        }
        $managedObject = $arguments[0];
        $actionArguments = isset($arguments[1]) ? $arguments[1] : [];
        return $this->makeSoapCall(
            $method,
            SoapMessage::makeUsingManagedObject($managedObject, $actionArguments)
        );
    }

    /**
     * @param $objectType
     * @param $propertiesToCollect
     *
     * @return mixed
     */
    public function findAllManagedObjects($objectType, $propertiesToCollect)
    {
        $propertyCollector = $this->getPropertyCollector();
        return $propertyCollector->collectAll($objectType, $propertiesToCollect);
    }

    /**
     * @param $objectType
     * @param $referenceId
     * @param $propertiesToCollect
     *
     * @return mixed
     */
    public function findOneManagedObject($objectType, $referenceId, $propertiesToCollect)
    {
        $propertyCollector = $this->getPropertyCollector();
        return $propertyCollector->collectPropertiesFor($objectType, $referenceId, $propertiesToCollect);
    }

    /**
     * todo: accept multi-level attributes (ie config->allocatedMemory->limit)
     * @param       $objectType
     * @param       $attributes
     * @param array $propertiesToCollect
     *
     * @return array
     */
    public function findManagedObjectByAttributes($objectType, $attributes, $propertiesToCollect = [])
    {
        $allObjects = $this->findAllManagedObjects($objectType, $propertiesToCollect);
        $objects = array_filter(
            $allObjects,
            function ($object) use ($attributes) {
                foreach ($attributes as $attribute => $value) {
                    if ($object->$attribute != $value) {
                        return false;
                    }
                }
                return true;
            }
        );
        return $objects;
    }

    /**
     * @return mixed
     */
    public function connect()
    {
        if ($this->session) {
            return $this->session;
        }
        $sessionManager = $this->getSessionManager();
        $this->session = $sessionManager->acquireSession($this->vhost->username, $this->vhost->password);
        return $this->session;
    }

    /**
     * @return mixed
     * @throws Exception\Soap
     */
    public function getServiceContent()
    {
        if (!$this->serviceContent) {
            $this->serviceContent = $this->makeSoapCall(
                'RetrieveServiceContent',
                SoapMessage::makeForServiceInstance()
            );
        }
        return $this->serviceContent;
    }

    /**
     * @param $response
     *
     * @return mixed
     */
    protected function convertResponse($response)
    {
        $responseVars = get_object_vars($response);
        if (isset($response->returnval) ||
            (array_key_exists('returnval', $responseVars) && is_null($responseVars['returnval']))
        ) {
            return $this->typeConverter->convert($response->returnval);
        }
        return $this->typeConverter->convert($response);
    }

    /**
     * @param $method
     * @param $soapMessage
     *
     * @return mixed
     * @throws SoapException
     */
    private function makeSoapCall($method, $soapMessage)
    {
        $this->soapClient->_classmap = $this->clientFactory->getClientClassMap();
        try {
            $result = $this->soapClient->$method($soapMessage);
        } catch (\SoapFault $soapFault) {
            $this->soapClient->_classmap = null;
            throw new SoapException($soapFault);
        }
        $this->soapClient->_classmap = null;
        return $this->convertResponse($result);
    }

    /**
     * @param $method
     * @param $arguments
     *
     * @return null
     */
    private function getQueriedProperty($method, $arguments)
    {
        $propertyToRetrieve = $this->generateNameForThePropertyToRetrieve($method);
        $content = $this->getServiceContent();
        if (isset($content->$propertyToRetrieve)) {
            return $content->$propertyToRetrieve;
        }
        $managedObject = $arguments[0];
        $foundManagedObject = $this->findOneManagedObject(
            $managedObject->getReferenceType(),
            $managedObject->getReferenceId(),
            [$propertyToRetrieve]
        );
        if (!isset($foundManagedObject->$propertyToRetrieve)) {
            return null;
        }
        return $foundManagedObject->$propertyToRetrieve;
    }

    /**
     * @param $calledMethod
     *
     * @return int
     */
    private function isMethodAPropertyRetrieval($calledMethod)
    {
        return preg_match('/^get/', $calledMethod);
    }

    /**
     * @param $calledMethod
     *
     * @return string
     */
    private function generateNameForThePropertyToRetrieve($calledMethod)
    {
        return lcfirst(substr($calledMethod, 3));
    }
}
