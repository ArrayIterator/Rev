<?php
declare(strict_types=1);

namespace ArrayIterator\Rev\Source\Http\Factory;

use ArrayIterator\Rev\Source\Http\Uri;
use Psr\Http\Message\UriFactoryInterface;
use Psr\Http\Message\UriInterface;

class UriFactory implements UriFactoryInterface
{
    public function createUri(string $uri = ''): UriInterface
    {
        return new Uri($uri);
    }
}
