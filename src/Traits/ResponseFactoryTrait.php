<?php
declare(strict_types=1);

namespace ArrayIterator\Rev\Source\Traits;

use ArrayIterator\Rev\Source\Http\Factory\ResponseFactory;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Throwable;

trait ResponseFactoryTrait
{
    abstract public function getContainer() : ?ContainerInterface;

    public function getResponseFactory() : ResponseFactoryInterface
    {
        $container = $this->getContainer();
        try {
            $factory = $container?->has(ResponseFactoryInterface::class)
                ? $container->get(ResponseFactoryInterface::class)
                : null;
        } catch (Throwable) {
            $factory = new ResponseFactory();
        }
        if (!$factory instanceof ResponseFactoryInterface) {
            $factory = new ResponseFactory();
        }
        return $factory;
    }
}
