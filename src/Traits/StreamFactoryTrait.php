<?php
declare(strict_types=1);

namespace ArrayIterator\Rev\Source\Traits;

use ArrayIterator\Rev\Source\Http\Factory\StreamFactory;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Throwable;

trait StreamFactoryTrait
{
    abstract public function getContainer() : ?ContainerInterface;

    protected function getStreamFactory() : StreamFactoryInterface
    {
        $container = $this->getContainer();
        try {
            $factory = $container?->has(StreamFactoryInterface::class)
                ? $container->get(StreamFactoryInterface::class)
                : null;
        } catch (Throwable) {
            $factory = new StreamFactory();
        }
        if (!$factory instanceof StreamFactoryInterface) {
            $factory = new StreamFactory();
        }
        return $factory;
    }
}
