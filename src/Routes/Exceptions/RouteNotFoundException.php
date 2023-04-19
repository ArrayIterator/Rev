<?php
declare(strict_types=1);

namespace ArrayIterator\Rev\Source\Routes\Exceptions;

use ArrayIterator\Rev\Source\Routes\Router;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;

class RouteNotFoundException extends RouteException
{
    public function __construct(
        Router $router,
        public readonly ServerRequestInterface $request,
        string $message = "",
        int $code = 0,
        ?Throwable $previous = null
    ) {
        $message = $message?:'Route Not Found';
        parent::__construct($router, $message, $code, $previous);
    }

    /**
     * @return ServerRequestInterface
     */
    public function getRequest(): ServerRequestInterface
    {
        return $this->request;
    }
}
