<?php
declare(strict_types=1);

namespace ArrayIterator\Rev\Source\Handlers\Interfaces;

use ArrayIterator\Rev\Source\Application;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;

interface ErrorHandlerInterface
{
    public function __invoke(
        Application $application,
        Throwable $exception,
        ServerRequestInterface $request
    ): ResponseInterface;
}
