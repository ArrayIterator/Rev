<?php
declare(strict_types=1);

namespace ArrayIterator\Rev\Source\Http\Request\Exceptions;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;
use RuntimeException;
use Throwable;

class BadResponseException extends RuntimeException
{
    /**
     * @var ResponseInterface
     */
    protected ResponseInterface $response;

    /**
     * @var RequestInterface
     */
    protected RequestInterface $request;

    protected array $info = [];

    public function __construct(
        RequestInterface $request,
        ResponseInterface $response,
        array $info = [],
        $message = "",
        $code = null,
        Throwable $previous = null
    ) {
        $this->info = $info;
        $this->request  = $request;
        $this->response = $response;
        $code ??= $this->response->getStatusCode();
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
     * @return ResponseInterface
     */
    public function getResponse(): ResponseInterface
    {
        return $this->response;
    }

    /**
     * @return RequestInterface
     */
    public function getRequest(): RequestInterface
    {
        return $this->request;
    }
}
