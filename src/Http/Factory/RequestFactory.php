<?php
declare(strict_types=1);

namespace ArrayIterator\Rev\Source\Http\Factory;

use ArrayIterator\Rev\Source\Http\Request;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\RequestInterface;

class RequestFactory implements RequestFactoryInterface
{
    public function createRequest(string $method, $uri): RequestInterface
    {
        return new Request($method, $uri);
    }
}
