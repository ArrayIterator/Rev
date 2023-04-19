<?php
declare(strict_types=1);

namespace ArrayIterator\Rev\Source\Http\Interfaces;

use Psr\Http\Message\ServerRequestInterface;
use Throwable;

interface HttpRequestResponseExceptionInterface extends Throwable
{
    public function __construct(
        ServerRequestInterface $request,
        string $message = '',
        int $code = 0,
        Throwable $previousException = null
    );

    public function getRequest() : ServerRequestInterface;

    public function getHttpCode() : int;
}
