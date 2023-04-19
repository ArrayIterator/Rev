<?php
declare(strict_types=1);

namespace ArrayIterator\Rev\Source\Http\Exceptions;

use ArrayIterator\Rev\Source\Http\Code;
use ArrayIterator\Rev\Source\Http\Interfaces\HttpRequestResponseExceptionInterface;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;

class NotFoundException extends HttpException implements HttpRequestResponseExceptionInterface
{
    protected ServerRequestInterface $request;

    public function __construct(
        ServerRequestInterface $request,
        string $message = '',
        int $code = 0,
        Throwable $previousException = null
    ) {
        $message = $message?:'The page you requested was not found';
        $this->request = $request;
        parent::__construct($message, $code, $previousException);
    }

    public function getRequest(): ServerRequestInterface
    {
        return $this->request;
    }

    public function getHttpCode(): int
    {
        return Code::NOT_FOUND;
    }
}
