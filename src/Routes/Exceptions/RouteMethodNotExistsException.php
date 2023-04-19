<?php
declare(strict_types=1);

namespace ArrayIterator\Rev\Source\Routes\Exceptions;

use ArrayIterator\Rev\Source\Routes\Controller;
use ArrayIterator\Rev\Source\Routes\Router;
use Throwable;

class RouteMethodNotExistsException extends RouteException
{
    public function __construct(
        Router $router,
        public readonly Controller $controller,
        public readonly string $method,
        string $message = "",
        int $code = 0,
        ?Throwable $previous = null
    ) {
        $message = $message?:sprintf(
            'Method %s on class %s is not exists',
            $this->method,
            $this->controller::class
        );
        parent::__construct($router, $message, $code, $previous);
    }
}
