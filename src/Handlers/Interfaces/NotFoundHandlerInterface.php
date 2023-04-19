<?php
declare(strict_types=1);

namespace ArrayIterator\Rev\Source\Handlers\Interfaces;

use ArrayIterator\Rev\Source\Application;
use ArrayIterator\Rev\Source\Routes\Exceptions\RouteNotFoundException;
use Psr\Http\Message\ResponseInterface;

interface NotFoundHandlerInterface
{
    public function __invoke(
        Application $application,
        RouteNotFoundException $exception
    ): ResponseInterface;
}
