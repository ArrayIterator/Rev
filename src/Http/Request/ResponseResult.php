<?php
declare(strict_types=1);

namespace ArrayIterator\Rev\Source\Http\Request;

use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

class ResponseResult
{
    protected RequestInterface $request;
    protected ResponseInterface $response;
    protected array $info;

    public function __construct(
        RequestInterface $request,
        ResponseInterface $response,
        array $info = []
    ) {
        $this->request = $request;
        $this->response = $response;
        $this->info = $info;
    }

    /**
     * @return RequestInterface
     */
    public function getRequest(): RequestInterface
    {
        return $this->request;
    }

    /**
     * @return ResponseInterface
     */
    public function getResponse(): ResponseInterface
    {
        return $this->response;
    }

    /**
     * @return array
     */
    public function getInfo(): array
    {
        return $this->info;
    }
}
