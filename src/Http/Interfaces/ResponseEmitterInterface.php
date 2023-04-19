<?php
declare(strict_types=1);

namespace ArrayIterator\Rev\Source\Http\Interfaces;

use Psr\Http\Message\ResponseInterface;

interface ResponseEmitterInterface
{
    public function emit(ResponseInterface $response, bool $reduceError = false);

    public function hasEmitted() : bool;
}
