<?php
namespace Vmwarephp\Extensions;

use \Vmwarephp\ManagedObject;

/**
 * Class Datastore
 * @package Vmwarephp\Extensions
 */
class Datastore extends ManagedObject
{
    /**
     * @return array
     */
    public function getConnectedHosts()
    {
        $hosts = [];
        foreach ($this->host as $hostMount) {
            if ($hostMount) {
                $hosts[] = $hostMount->key;
            }
        }
        return $hosts;
    }

    /**
     * @return array
     */
    public function getVirtualMachinesReferencingThisDatastore()
    {
        if (!$this->vm[0]) {
            return [];
        }
        return array_filter(
            $this->vm,
            function ($aVm) {
                return !$aVm->isTemplate();
            }
        );
    }

    /**
     * @return array
     */
    public function getVirtualMachinesInstalledOnThisDatastore()
    {
        $vms = [];
        foreach ($this->getVirtualMachinesReferencingThisDatastore() as $vm) {
            if ($vm->getParentDatastoreName() == $this->name) {
                $vms[] = $vm;
            }
        }
        return $vms;
    }

    /**
     * @return bool
     */
    public function isAccessible()
    {
        return in_array($this->configStatus, ['green', 'gray']);
    }
}
