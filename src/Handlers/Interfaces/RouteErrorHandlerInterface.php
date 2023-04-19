<?php
declare(strict_types=1);

namespace ArrayIterator\Rev\Source\Handlers\Interfaces;

use ArrayIterator\Rev\Source\Application;
use ArrayIterator\Rev\Source\Routes\Exceptions\RouteErrorException;
use Psr\Http\Message\ResponseInterface;

interface RouteErrorHandlerInterface
{
    public function __invoke(
        Application $application,
        RouteErrorException $exception
    ): ResponseInterface;
}
