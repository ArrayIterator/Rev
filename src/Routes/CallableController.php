<?php
declare(strict_types=1);

namespace ArrayIterator\Rev\Source\Routes;

use Closure;
use Psr\Http\Message\ResponseInterface;
use ReflectionException;
use ReflectionFunction;

final class CallableController extends Controller
{
    /**
     * @var callable
     */
    private $callback;

    protected function route(...$arguments)
    {
        $callback = $this->callback;
        unset($this->callback);
        try {
            if ($callback instanceof Closure) {
                $ref = new ReflectionFunction($callback);
                if (!$ref->isStatic() && !$ref->getClosureThis()) {
                    $callback = $callback->bindTo($this, $this);
                }
            }
        } catch (ReflectionException) {
        }
        return $callback(...$arguments);
    }

    public static function attach(Router $router, callable $callback): CallableController
    {
        $router = new self($router);
        $router->callback = $callback;
        return $router;
    }
}
