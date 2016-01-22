<?php
namespace Vmwarephp;

/**
 * Class WsdlClassMapper
 * @package Vmwarephp
 */
class WsdlClassMapper
{
    private $classDefinitionsFilePath;
    private $useClassMapCaching = true;
    private $classMapCacheFile;

    /**
     * WsdlClassMapper constructor.
     *
     * @param null $classDefinitionsFilePath
     */
    public function __construct($classDefinitionsFilePath = null)
    {
        $this->classDefinitionsFilePath = $classDefinitionsFilePath ?: dirname(__FILE__) . '/TypeDefinitions.inc';
    }

    /**
     * @return array|mixed|void
     * @throws \Exception
     */
    public function getClassMap()
    {
        $classMap = $this->readClassMapFromCache();
        if ($classMap) {
            return $classMap;
        }
        $classMap = $this->generateClassMap();
        $this->cacheClassMap($classMap);
        return $classMap;
    }

    /**
     * @param bool $useCaching
     */
    public function configureClassMapCaching($useCaching = true)
    {
        $this->useClassMapCaching = $useCaching;
    }

    /**
     * @return mixed|null
     */
    private function readClassMapFromCache()
    {
        $cacheFilePath = $this->getClassMapCacheFile();
        if (!file_exists($cacheFilePath) || !$this->useClassMapCaching) {
            return null;
        }
        return unserialize(file_get_contents($cacheFilePath));
    }

    /**
     * @return array
     */
    private function generateClassMap()
    {
        $classMap = [];
        $allTokens = token_get_all($this->readClassDefinitions());
        foreach ($allTokens as $key => $token) {
            if ($this->tokenRepresentsClassDefinition($token)) {
                $className = $allTokens[$key + 2][1];
                $classMap[$className] = $className;
            }
        }
        return array_merge($classMap, $this->getExtendedClasses());
    }

    /**
     * @return array
     */
    private function getExtendedClasses()
    {
        $classes = [];
        foreach (scandir(__DIR__ . '/Extensions/') as $fileName) {
            if (in_array($fileName, ['.', '..'])) {
                continue;
            }
            $classNameComponents = explode('.', $fileName);
            $className = $classNameComponents[0];
            $classes[$className] = '\\Vmwarephp\\Extensions\\' . $className;
        }
        return $classes;
    }

    /**
     * @param $token
     *
     * @return bool
     */
    private function tokenRepresentsClassDefinition($token)
    {
        return is_array($token) && $token[0] == T_CLASS;
    }

    /**
     * @return string
     */
    private function readClassDefinitions()
    {
        if (!file_exists($this->classDefinitionsFilePath)) {
            return '';
        }
        return file_get_contents($this->classDefinitionsFilePath);
    }

    /**
     * @param $classMap
     *
     * @throws \Exception
     */
    private function cacheClassMap($classMap)
    {
        if (!$this->useClassMapCaching) {
            return;
        }
        if (!file_put_contents($this->getClassMapCacheFile(), serialize($classMap))) {
            throw new \Exception(
                '\\Vmwarephp\\WsdlClassMapper is configured to cache the class map but was not able to. Check the ' .
                'permissions on the cache directory.'
            );
        }
    }

    /**
     * @return string
     */
    private function getClassMapCacheFile()
    {
        if (!$this->classMapCacheFile) {
            $this->classMapCacheFile = __DIR__ . '/' . '.wsdl_class_map.cache';
        }
        return $this->classMapCacheFile;
    }
}
