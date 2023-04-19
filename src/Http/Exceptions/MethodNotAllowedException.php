<?php
declare(strict_types=1);

namespace ArrayIterator\Rev\Source\Http\Exceptions;

use ArrayIterator\Rev\Source\Http\Code;
use ArrayIterator\Rev\Source\Http\Interfaces\HttpRequestResponseExceptionInterface;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;

class MethodNotAllowedException extends HttpException implements HttpRequestResponseExceptionInterface
{
    protected ServerRequestInterface $request;

    protected array $allowedMethods;

    public function __construct(
        ServerRequestInterface $request,
        string $message = '',
        int $code = 0,
        Throwable $previousException = null,
        array $allowedMethods = []
    ) {
        $this->allowedMethods = $allowedMethods;
        $this->request = $request;
        $message = $message?:'Method Not Allowed';
        parent::__construct($message, $code, $previousException);
    }

    public function getRequest(): ServerRequestInterface
    {
        return $this->request;
    }

    public function getHttpCode(): int
    {
        return Code::METHOD_NOT_ALLOWED;
    }

    /**
     * @return array
     */
    public function getAllowedMethods(): array
    {
        return $this->allowedMethods;
    }
}
