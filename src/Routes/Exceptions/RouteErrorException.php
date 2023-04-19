<?php
declare(strict_types=1);

namespace ArrayIterator\Rev\Source\Routes\Exceptions;

use ArrayIterator\Rev\Source\Routes\Route;
use ArrayIterator\Rev\Source\Routes\Router;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;

class RouteErrorException extends RouteException
{
    public function __construct(
        Router $router,
        public readonly Route $route,
        public readonly ServerRequestInterface $request,
        public readonly Throwable $exception,
        string $message = "",
        int $code = null,
        ?Throwable $previous = null
    ) {
        $message = $message?:$this->exception->getMessage();
        $code ??= $this->exception->getCode();
        parent::__construct($router, $message, $code, $previous);
    }

    /**
     * @return ServerRequestInterface
     */
    public function getRequest(): ServerRequestInterface
    {
        return $this->request;
    }

    /**
     * @return Throwable
     */
    public function getException(): Throwable
    {
        return $this->exception;
    }

    /**
     * @return Route
     */
    public function getRoute(): Route
    {
        return $this->route;
    }
}
