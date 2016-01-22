<?php

namespace Vmwarephp;

/**
 * Class Autoloader
 * @package Vmwarephp
 */
class Autoloader
{
    private $fileExtension = '.php';
    private $namespace;
    private $includePath;
    private $namespaceSeparator = '\\';

    /**
     * Autoloader constructor.
     *
     * @param string $ns
     * @param null   $includePath
     */
    public function __construct($ns = 'Vmwarephp', $includePath = null)
    {
        $this->namespace = $ns;
        $this->includePath = $includePath ?: $this->getLibraryPath();
    }

    /**
     * @param $sep
     */
    public function setNamespaceSeparator($sep)
    {
        $this->namespaceSeparator = $sep;
    }

    /**
     * @return string
     */
    public function getNamespaceSeparator()
    {
        return $this->namespaceSeparator;
    }

    /**
     * @param $includePath
     */
    public function setIncludePath($includePath)
    {
        $this->includePath = $includePath;
    }

    /**
     * @return string
     */
    public function getIncludePath()
    {
        return $this->includePath;
    }

    /**
     * @param $fileExtension
     */
    public function setFileExtension($fileExtension)
    {
        $this->fileExtension = $fileExtension;
    }

    /**
     * @return string
     */
    public function getFileExtension()
    {
        return $this->fileExtension;
    }

    /**
     * Autoload register
     */
    public function register()
    {
        spl_autoload_register(array($this, 'loadClass'));
    }

    /**
     * Autoload unregister
     */
    public function unregister()
    {
        spl_autoload_unregister(array($this, 'loadClass'));
    }

    /**
     * @param $className
     *
     * @return mixed|null
     */
    public function loadClass($className)
    {
        if (strpos($className, $this->namespace) === false) {
            require_once 'TypeDefinitions.inc';
            return null;
        }
        if (is_null($this->namespace) ||
            $this->namespace . $this->namespaceSeparator === substr(
                $className,
                0,
                strlen($this->namespace . $this->namespaceSeparator)
            )
        ) {
            $fileDirectoryPath = '';
            $lastNsPos = strripos($className, $this->namespaceSeparator);
            if ($lastNsPos !== false) {
                $namespace = $this->getNamespaceFromClassName($className, $lastNsPos);
                $shortClassName = $this->getShortClassName($className, $lastNsPos);
                $fileDirectoryPath .= $this->namespaceToPath($namespace);
            }
            $absolutePath = $this->makeAbsolutePath($fileDirectoryPath, $shortClassName);
            if (file_exists($absolutePath)) {
                return require_once $absolutePath;
            }
        }
        return null;
    }

    /**
     * @param $namespace
     *
     * @return string
     */
    private function namespaceToPath($namespace)
    {
        return str_replace($this->namespaceSeparator, DIRECTORY_SEPARATOR, $namespace) . DIRECTORY_SEPARATOR;
    }

    /**
     * @param $className
     * @param $lastNsPos
     *
     * @return string
     */
    private function getShortClassName($className, $lastNsPos)
    {
        return substr($className, $lastNsPos + 1);
    }

    /**
     * @param $className
     * @param $lastNsPos
     *
     * @return string
     */
    private function getNamespaceFromClassName($className, $lastNsPos)
    {
        return substr($className, 0, $lastNsPos);
    }

    /**
     * @param $fileDirectory
     * @param $shortClassName
     *
     * @return string
     */
    private function makeAbsolutePath($fileDirectory, $shortClassName)
    {
        return $this->includePath . DIRECTORY_SEPARATOR . $fileDirectory . $shortClassName . $this->fileExtension;
    }

    /**
     * @return string
     */
    private function getLibraryPath()
    {
        return __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR;
    }
}
