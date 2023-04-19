<?php
declare(strict_types=1);

namespace ArrayIterator\Rev\Source\Traits;

use ArrayIterator\Rev\Source\Http\Factory\RequestFactory;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Throwable;

trait RequestFactoryTrait
{
    abstract public function getContainer() : ?ContainerInterface;

    protected function getRequestFactory() : RequestFactory
    {
        $container = $this->getContainer();
        try {
            $factory = $container?->has(RequestFactoryInterface::class)
                ? $container->get(RequestFactoryInterface::class)
                : null;
        } catch (Throwable) {
            $factory = new RequestFactory();
        }
        if (!$factory instanceof RequestFactoryInterface) {
            $factory = new RequestFactory();
        }
        return $factory;
    }
}
