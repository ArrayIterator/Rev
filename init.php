<?php
use ArrayIterator\Rev\Source\Kernel;
use ArrayIterator\Rev\Source\Storage\ObjectContainer;
use Composer\Autoload\ClassLoader;

return (function () {
    /**
     * @var ClassLoader $autoload
     */
    $autoload = require __DIR__ .'/vendor/autoload.php';
    $app = Kernel::prepare();
    try {
        $directories = new DirectoryIterator(__DIR__ . '/app/Containers');
    } catch (Throwable) {
        // skip
        return $app;
    }

    $namespace = "ArrayIterator\\Rev\\App\\Containers\\";
    $namespaceDirectory = $directories->getRealPath();
    $prefixes = $autoload->getPrefixesPsr4()[$namespace]??[];
    $found = false;
    foreach ($prefixes as $prefix) {
        if ($found) {
            break;
        }
        $found = realpath($prefix) === $namespaceDirectory;
    }

    if (!$found) {
        $autoload->addPsr4($namespace, $namespaceDirectory);
        $autoload->register();
    }
    $objects = [];
    foreach ($directories as $directory) {
        if ($directory->isDot() || !$directory->isFile()) {
            continue;
        }
        $name = $directory->getBasename();
        if (!str_ends_with($name, '.php')) {
            continue;
        }
        try {
            require_once $directory->getRealPath();
            $className = sprintf('%s%s', $namespace, substr($name, 0, -4));
            $object = new ReflectionClass($className);
            if (!$object->isInstantiable() || !$object->isSubclassOf(ObjectContainer::class)) {
                continue;
            }
            $object = $object->newInstance();
            /**
             * @var ObjectContainer $object
             */
            $objects[$object->getPriority()][$object->getId()] = $object;
        } catch (Throwable $e) {
            continue;
        }
    }

    ksort($objects);
    $container = $app->getContainer();
    foreach ($objects as $objectContainer) {
        ksort($objectContainer);
        foreach ($objectContainer as $object) {
            $container->set($object->getId(), $object);
        }
    }
})();
