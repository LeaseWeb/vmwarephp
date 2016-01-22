<?php
namespace Vmwarephp;

/**
 * Class SoapClient
 * @package Vmwarephp
 */
class SoapClient extends \SoapClient
{
    /**
     * @param string $request
     * @param string $location
     * @param string $action
     * @param int    $version
     * @param int    $one_way
     *
     * @return string
     * @throws
     */
    public function __doRequest($request, $location, $action, $version, $one_way = 0)
    {
        $request = $this->appendXsiTypeForExtendedDatastructures($request);
        $result = parent::__doRequest($request, $location, $action, $version, $one_way);
        if (isset($this->__soap_fault) && $this->__soap_fault) {
            throw $this->__soap_fault;
        }
        return $result;
    }

    /**
     * PHP does not provide inheritance information for wsdl types so we have to specify that its and xsi:type
     * php bug #45404
     * @param $request
     *
     * @return mixed
     */
    private function appendXsiTypeForExtendedDatastructures($request)
    {
        return $request = str_replace(
            ["xsi:type=\"ns1:TraversalSpec\"", '<ns1:selectSet />'],
            ["xsi:type=\"ns1:TraversalSpec\"", ''],
            $request
        );
    }
}
