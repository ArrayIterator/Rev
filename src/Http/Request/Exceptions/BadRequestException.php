<?php
declare(strict_types=1);

namespace ArrayIterator\Rev\Source\Http\Request\Exceptions;

use Psr\Http\Message\RequestInterface;
use RuntimeException;
use Throwable;

class BadRequestException extends RuntimeException
{
    protected RequestInterface $request;
    protected array $info;
    public function __construct(
        RequestInterface $request,
        array $info = [],
        $message = "",
        $code = 0,
        Throwable $previous = null
    ) {
        $this->request = $request;
        $this->info = $info;
        parent::__construct($message, $code, $previous);
    }

    /**
     * @return array
     */
    public function getInfo(): array
    {
        return $this->info;
    }

    /**
     * @return RequestInterface
     */
    public function getRequest(): RequestInterface
    {
        return $this->request;
    }
}
