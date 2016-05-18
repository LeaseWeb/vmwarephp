<?php
namespace Vmwarephp\Factory;

use \Vmwarephp\Exception as Ex;
use \Vmwarephp\WsdlClassMapper as MainWsdlClassMapper;
use \Vmwarephp\Vhost;
use \Vmwarephp\SoapClient as MainSoapClient;

/**
 * Class SoapClient
 * @package Vmwarephp\Factory
 */
class SoapClient
{
    private $wsdlClassMapper;
    private $wsdlFilePath;

    /**
     * SoapClient constructor.
     *
     * @param MainWsdlClassMapper|null $mapper
     * @param null                     $wsdlFilePath
     */
    public function __construct(MainWsdlClassMapper $mapper = null, $wsdlFilePath = null)
    {
        $this->wsdlClassMapper = $mapper ?: new MainWsdlClassMapper;
        $this->wsdlFilePath = $wsdlFilePath ?: $this->getWsdlFilePath();
    }

    /**
     * @param Vhost $vhost
     * @param int   $useExceptions
     * @param int   $trace
     *
     * @return MainSoapClient
     * @throws Ex\CannotCreateSoapClient
     */
    public function make(Vhost $vhost, $useExceptions = 1, $trace = 1)
    {
        $context = stream_context_create([
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => false
            ]
        ]);

        $options = [
            'trace' => $trace,
            'location' => $this->makeRequestsLocation($vhost),
            'exceptions' => $useExceptions,
            'connection_timeout' => 10,
            'classmap' => $this->wsdlClassMapper->getClassMap(),
            'features' => SOAP_SINGLE_ELEMENT_ARRAYS + SOAP_USE_XSI_ARRAY_TYPE,
            'stream_context' => $context
        ];
        $soapClient = $this->makeDefaultSoapClient($this->wsdlFilePath, $options);
        if (!$soapClient) {
            throw new Ex\CannotCreateSoapClient();
        }
        return $soapClient;
    }

    /**
     * @return array|mixed|void
     */
    public function getClientClassMap()
    {
        return $this->wsdlClassMapper->getClassMap();
    }

    /**
     * @param Vhost $vhost
     *
     * @return string
     */
    protected function makeRequestsLocation(Vhost $vhost)
    {
        return 'https://' . $vhost->host . '/sdk';
    }

    /**
     * @param       $wsdl
     * @param array $options
     *
     * @return \Vmwarephp\SoapClient
     */
    protected function makeDefaultSoapClient($wsdl, array $options)
    {
        return @new MainSoapClient($wsdl, $options);
    }

    /**
     * @return string
     */
    private function getWsdlFilePath()
    {
        return __DIR__ . '/../Wsdl/vimService.wsdl';
    }
}
