<?php
declare(strict_types=1);

namespace ArrayIterator\Rev\Source\Storage;

use Psr\Container\ContainerInterface;

abstract class ObjectContainer
{
    final public function __construct()
    {
    }

    public function getPriority() : int
    {
        return 10;
    }

    abstract public function getId() : string;

    abstract public function __invoke(ContainerInterface $container);
}
