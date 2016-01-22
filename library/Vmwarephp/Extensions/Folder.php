<?php
namespace Vmwarephp\Extensions;

use \Vmwarephp\ManagedObject;

/**
 * Class Folder
 * @package Vmwarephp\Extensions
 */
class Folder extends ManagedObject
{
    /**
     * @param $type   string The type of ManagedObject to find
     * @param $name   string The name of the ManagedObject to find
     * @param $create bool   Whether or not to create a folder with the name $name if it doesn't exist.
     * @return bool
     * @throws \Exception
     */
    public function getChild($type = '', $name = '', $create = false)
    {
        if (!$type || !$name) {
            throw new \Exception('Folder::getChild requires $type and $child arguments');
        }

        foreach ($this->childEntity as $child) {
            if (!is_object($child)) {
                continue;
            }
            if ($child->getReferenceType() === $type) {
                if ($child->name === $name) {
                    return $child;
                }
            }
        }

        if ($create && $type === 'Folder') {
            return $this->createFolder(['name' => $name]);
        }

        return false;
    }

    /**
     * @param string $type
     *
     * @return array|mixed
     */
    public function getChildren($type = '')
    {
        if (!$type) {
            return $this->childEntity;
        }

        $children = [];

        foreach ($this->childEntity as $child) {
            if (!is_object($child)) {
                continue;
            }
            if ($child->getReferenceType() === $type) {
                $children[] = $child;
            }
        }

        return $children;
    }

    /**
     * @param $path   string A folder path delimited with '/' like Folder1/Folder2/etc which would attempt to find
     *                Folder1 as a child of this Folder, Folder2 as a child of Folder1, etc as a child of Folder2.
     * @param $create bool   Whether or not to create the folders described by the path if they don't exist.
     * @return bool
     * @throws \Exception
     */
    public function getFolderByPath($path = '', $create = false)
    {
        if (!$path) {
            throw new \Exception('Folder::getFolderByPath requires a $path argument');
        }
        $folderNames = explode('/', $path);
        $name = $path;
        $newPath = '';

        if (count($folderNames) > 1) {
            $name = $folderNames[0];
            $newPath = str_replace($name . '/', '', $path);
        }

        $childFolder = $this->getChild('Folder', $name);
        if (!$childFolder) {
            if ($create) {
                $childFolder = $this->createFolder(['name' => $name]);
            } else {
                return false;
            }
        }
        if (!$newPath) {
            return $childFolder;
        }

        return $childFolder->getFolderByPath($newPath, $create);
    }
}
