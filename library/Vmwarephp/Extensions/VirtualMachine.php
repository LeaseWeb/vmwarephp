<?php
namespace Vmwarephp\Extensions;

use \Vmwarephp\ManagedObject;

/**
 * Class VirtualMachine
 * @package Vmwarephp\Extensions
 */
class VirtualMachine extends ManagedObject
{
    /**
     * @param        $name
     * @param null   $memory
     * @param null   $quiesce
     * @param string $description
     *
     * @return mixed
     */
    public function takeSnapshot($name, $memory = null, $quiesce = null, $description = '')
    {
        $snapshotTask = $this->CreateSnapshot_Task(
            ['name' => $name, 'description' => $description, 'memory' => $memory, 'quiesce' => $quiesce]
        );
        return $snapshotTask;
    }

    /**
     * @return bool
     */
    public function isAccessible()
    {
        return in_array($this->configStatus, ['green', 'gray']);
    }

    /**
     * @return mixed
     */
    public function isTemplate()
    {
        return $this->summary->config->template;
    }

    /**
     * @return mixed
     */
    public function getParentDatastoreName()
    {
        preg_match('/\[(.*)\]/', $this->summary->config->vmPathName, $matches);
        return $matches[1];
    }

    /**
     * @return mixed
     */
    public function getParentDatastore()
    {
        foreach ($this->datastore as $datastore) {
            if ($datastore->name == $this->getParentDatastoreName()) {
                return $datastore;
            }
        }
        return null;
    }

    /**
     * @return bool
     */
    public function hasSnapshots()
    {
        return $this->snapshot ? true : false;
    }

    /**
     * @return mixed
     */
    public function getUsedSpace()
    {
        return $this->summary->storage->committed;
    }

    /**
     * @return mixed
     */
    public function getProvisionedSpace()
    {
        return $this->summary->storage->committed + $this->summary->storage->uncommitted;
    }

    /**
     * @return mixed
     */
    public function getHardware()
    {
        return $this->config->hardware;
    }

    /**
     * @return mixed
     */
    public function getGuestInfo()
    {
        return $this->guest;
    }
}
