<?php
declare(strict_types=1);

namespace ArrayIterator\Rev\Source\Routes;

use ArrayIterator\Rev\Source\Events\EventsManager;
use ArrayIterator\Rev\Source\Events\EventsManagerInterface;
use ArrayIterator\Rev\Source\Routes\Attributes\Any;
use ArrayIterator\Rev\Source\Routes\Attributes\Group;
use ArrayIterator\Rev\Source\Routes\Attributes\Interfaces\HttpMethodAttributeInterface;
use ArrayIterator\Rev\Source\Routes\Exceptions\RouteControllerException;
use ArrayIterator\Rev\Source\Routes\Exceptions\RouteErrorException;
use ArrayIterator\Rev\Source\Routes\Exceptions\RouteNotFoundException;
use ArrayIterator\Rev\Source\Traits\BenchmarkingTrait;
use ArrayIterator\Rev\Source\Traits\ResponseFactoryTrait;
use ArrayIterator\Rev\Source\Traits\ServerRequestFactoryTrait;
use Closure;
use InvalidArgumentException;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use ReflectionAttribute;
use ReflectionClass;
use ReflectionException;
use ReflectionFunction;
use Throwable;

class Router
{
    use BenchmarkingTrait,
        ResponseFactoryTrait,
        ServerRequestFactoryTrait;

    const REGEX_DELIMITER = [
        '~', '#', '@', '=', '+', '`'
    ];

    protected array $registeredControllers = [];

    /**
     * @var array<Route>
     */
    protected array $registeredRoutes = [];

    /**
     * @var array<Route>
     */
    protected array $deferredRoutes = [];

    protected array $prefixes = [];

    /**
     * @var ?Route
     */
    protected ?Route $matchedRoute = null;

    /**
     * @var array{0:array, 1:string, 2:string}
     */
    protected array $matchedParams = [];

    private bool $dispatched = false;

    /**
     * @var ?ResponseInterface
     */
    private ?ResponseInterface $response = null;

    private ?ServerRequestInterface $dispatchedRequest = null;

    private ?ContainerInterface $container = null;

    protected ?EventsManager $eventsManager = null;

    private ?Controller $dispatchedController = null;

    public function __construct(
        ?ContainerInterface $container = null,
        ?EventsManagerInterface $eventsManager = null
    ) {
        if ($container) {
            $this->setContainer($container);
        }
        if ($eventsManager) {
            $this->setEventsManager($eventsManager);
        }
    }

    /**
     * @return bool
     */
    public function isDispatched(): bool
    {
        return $this->dispatched;
    }

    /**
     * @return ServerRequestInterface|null
     */
    public function getDispatchedRequest(): ?ServerRequestInterface
    {
        return $this->dispatchedRequest;
    }

    /**
     * @return array{0:array, 1:string,2:string}
     */
    public function getMatchedParams(): array
    {
        return $this->matchedParams;
    }

    /**
     * @return ContainerInterface|null
     */
    public function getContainer(): ?ContainerInterface
    {
        return $this->container;
    }

    /**
     * @param ContainerInterface $container
     */
    public function setContainer(ContainerInterface $container): void
    {
        $this->container = $container;
    }

    public function getMatchedRoute() : ?Route
    {
        return $this->matchedRoute;
    }

    /**
     * @return ?EventsManager
     */
    public function getEventsManager(): ?EventsManager
    {
        return $this->eventsManager;
    }

    /**
     * @param EventsManager $eventsManager
     */
    public function setEventsManager(EventsManager $eventsManager): void
    {
        $this->eventsManager = $eventsManager;
    }

    public function group(string $pattern, callable $callback): static
    {
        $this->prefixes[] = $pattern;
        if ($callback instanceof Closure) {
            try {
                $ref = new ReflectionFunction($callback);
                if (!$ref->isStatic() && !$ref->getClosureThis()) {
                    $callback = $callback->bindTo($this);
                }
            } catch (ReflectionException) {
            }
        }
        $callback($this);
        array_pop($this->prefixes);
        return $this;
    }

    public function addRoute(
        Route $route
    ): static {
        if ($this->isDispatched()) {
            $this->deferredRoutes[spl_object_hash($route)] = $route;
        } else {
            $this->registeredRoutes[spl_object_hash($route)] = $route;
        }
        return $this;
    }

    public function removeRoute(
        Route $route
    ) : bool {
        $id = spl_object_hash($route);
        if (!isset($this->registeredRoutes[$id])
            && !isset($this->deferredRoutes[$id])
        ) {
            return false;
        }
        unset($this->registeredRoutes[$id], $this->deferredRoutes[$id]);
        return true;
    }

    public function hasRoute(Route $route): bool
    {
        return isset($this->registeredRoutes[spl_object_hash($route)]);
    }

    /**
     * @return array<Route>
     */
    public function getRegisteredRoutes(): array
    {
        return $this->registeredRoutes;
    }

    /**
     * @return array<Route>
     */
    public function getDeferredRoutes(): array
    {
        return $this->deferredRoutes;
    }

    public function isDeferred(Route $route): bool
    {
        return isset($this->deferredRoutes[spl_object_hash($route)]);
    }

    /**
     * @param string|Controller $controller
     * @return array<Route>
     */
    public function addRouteController(string|Controller $controller): array
    {
        $benchmark = null;
        try {
            try {
                $ref = new ReflectionClass($controller);
            } catch (Throwable $e) {
                $benchmark = $this->benchmarkStart(
                    name: 'controller',
                    context: [
                        'controller' => is_object($controller) ? $controller::class : get_class($controller)
                    ],
                    group: 'router'
                );
                throw new InvalidArgumentException(
                    $e->getMessage()
                );
            }
            $benchmark = $this->benchmarkStart(
                name: 'controller',
                context: [
                    'controller' => $ref->getName()
                ],
                group: 'router'
            );

            if (!$ref->isSubclassOf(Controller::class)) {
                throw new InvalidArgumentException(
                    sprintf('Argument must be subclass of %s', Controller::class)
                );
            }

            $group = $ref->getAttributes(Group::class)[0]??null;
            $prefix = $group?->newInstance()->getPattern()??'';
            $routes = [];
            $className = $ref->getName();
            foreach ($ref->getMethods() as $method) {
                if ($method->getDeclaringClass()->getName() !== $className) {
                    continue;
                }

                $attributes = $method->getAttributes(Any::class);
                $attributes = empty($attributes) ? $method->getAttributes(
                    HttpMethodAttributeInterface::class,
                    ReflectionAttribute::IS_INSTANCEOF
                ) : $attributes;

                $methodName = $method->getName();
                if (isset($this->registeredControllers[$className][$methodName])) {
                    foreach ($this->registeredControllers[$className][$methodName] as $id) {
                        if (isset($this->registeredRoutes[$id])) {
                            unset($this->registeredRoutes[$id]);
                        }
                    }
                }

                $this->registeredControllers[$className][$methodName] = [];
                foreach ($attributes as $attribute) {
                    try {
                        $attribute = $attribute->newInstance();
                    } catch (Throwable $e) {
                        throw new RouteControllerException(
                            $this,
                            $e,
                            $e->getMessage()
                        );
                    }
                    if (!$attribute instanceof HttpMethodAttributeInterface) {
                        continue;
                    }
                    $route = $this->add(
                        methods: $attribute->getMethods(),
                        pattern: $prefix . $attribute->getPattern(),
                        controller: [$controller, $method->getName()],
                        priority: $attribute->getPriority(),
                        name: $attribute->getName(),
                        hostName: $attribute->getHostName()
                    );
                    $id = spl_object_hash($route);
                    $this->registeredControllers[$className][$methodName][] = $id;
                    $routes[$id] = $route;
                }
            }
            return $routes;
        } finally {
            $benchmark?->stop();
        }
    }

    public function add(
        array|string $methods,
        string $pattern,
        callable|array $controller,
        ?int $priority = null,
        ?string $name = null,
        ?string $hostName = null
    ): Route {
        $prefix = implode('/', $this->prefixes);
        $benchmark = $this->benchmarkStart(
            name: 'map',
            context: [
                'prefix' => $prefix,
                'pattern' => $pattern,
            ],
            group: 'router'
        );
        $route = new Route(
            methods: $methods,
            pattern: $prefix . $pattern,
            controller: $controller,
            priority: $priority,
            name: $name,
            hostName: $hostName
        );
        $this->addRoute($route);
        $benchmark->stop(context: [
            'route' => $route
        ]);
        return $route;
    }

    public function get(
        string $pattern,
        callable|array $controller,
        ?int $priority = null,
        ?string $name = null,
        ?string $hostName = null
    ): Route {
        return $this->add('GET', $pattern, $controller, $priority, $name, $hostName);
    }

    public function any(
        string $pattern,
        callable|array $controller,
        ?int $priority = null,
        ?string $name = null,
        ?string $hostName = null
    ): Route {
        return $this->add('*', $pattern, $controller, $priority, $name, $hostName);
    }

    public function post(
        string $pattern,
        callable|array $controller,
        ?int $priority = null,
        ?string $name = null,
        ?string $hostName = null
    ): Route {
        return $this->add('POST', $pattern, $controller, $priority, $name, $hostName);
    }
    public function put(
        string $pattern,
        callable|array $controller,
        ?int $priority = null,
        ?string $name = null,
        ?string $hostName = null
    ): Route {
        return $this->add('PUT', $pattern, $controller, $priority, $name, $hostName);
    }

    public function delete(
        string $pattern,
        callable|array $controller,
        ?int $priority = null,
        ?string $name = null,
        ?string $hostName = null
    ): Route {
        return $this->add('DELETE', $pattern, $controller, $priority, $name, $hostName);
    }

    public function head(
        string $pattern,
        callable|array $controller,
        ?int $priority = null,
        ?string $name = null,
        ?string $hostName = null
    ): Route {
        return $this->add('HEAD', $pattern, $controller, $priority, $name, $hostName);
    }
    public function options(
        string $pattern,
        callable|array $controller,
        ?int $priority = null,
        ?string $name = null,
        ?string $hostName = null
    ): Route {
        return $this->add('OPTIONS', $pattern, $controller, $priority, $name, $hostName);
    }

    public function connect(
        string $pattern,
        callable|array $controller,
        ?int $priority = null,
        ?string $name = null,
        ?string $hostName = null
    ): Route {
        return $this->add('CONNECT', $pattern, $controller, $priority, $name, $hostName);
    }

    public function patch(
        string $pattern,
        callable|array $controller,
        ?int $priority = null,
        ?string $name = null,
        ?string $hostName = null
    ): Route {
        return $this->add('PATCH', $pattern, $controller, $priority, $name, $hostName);
    }

    public function trace(
        string $pattern,
        callable|array $controller,
        ?int $priority = null,
        ?string $name = null,
        ?string $hostName = null
    ): Route {
        return $this->add('TRACE', $pattern, $controller, $priority, $name, $hostName);
    }

    public function dispatch(ServerRequestInterface $request): ?Route
    {
        if ($this->isDispatched()) {
            return $this->getMatchedRoute();
        }

        $this->dispatched = true;
        uasort($this->registeredRoutes, static function (Route $a, Route $b) {
            return $a->getPriority() === $b->getPriority() ? 0 : (
                $a->getPriority() < $b->getPriority() ? -1 : 1
            );
        });

        $eventsManager = $this->getEventsManager();
        $dispatchedRequest = $eventsManager
            ?->dispatch('route.serverRequest', $request, $this);
        $dispatchedRequest = $dispatchedRequest instanceof ServerRequestInterface
            ? $dispatchedRequest
            : $request;

        $eventsManager
            ?->dispatch(
                'route.before.looping',
                $this,
                $dispatchedRequest,
                $request
            );

        $uri = $dispatchedRequest->getUri();
        $httpMethod = $dispatchedRequest->getMethod();
        $hostName = strtolower($uri->getHost());
        $path = $uri->getPath();
        $this->dispatchedRequest = $dispatchedRequest;
        set_error_handler(static function () {
            error_clear_last();
        });
        foreach ($this->getRegisteredRoutes() as $route) {
            $host = $route->getHostName();
            if ($hostName
                && is_string($host)
                && strtolower($host) !== $hostName
            ) {
                continue;
            }
            if (!$route->containMethod($httpMethod)) {
                continue;
            }

            $compiledPattern = $route->getCompiledPattern();
            $delimiter = $compiledPattern[0]??null;
            if (is_string($delimiter)
                && in_array($delimiter, self::REGEX_DELIMITER)
                && str_ends_with($compiledPattern, $delimiter)
            ) {
                $compiledPattern = substr($compiledPattern, 1, -1);
            }

            $compiledPattern = addcslashes($compiledPattern, '#');
            $pattern = $compiledPattern;
            // if contains start with "^", eg: ^/path
            $skip_first = str_starts_with($pattern, '^');
            // if contains $, eg: path/$
            $skip_last = str_ends_with($pattern, '$');
            if (!$skip_first) {
                $pattern = "^(/*)?{$pattern}";
            }
            if (!$skip_last) {
                if (preg_match('#(^|[^/])?(?:/|\[/])$#', $pattern)) {
                    $pattern .= '*';
                }
                $pattern = "{$pattern}(/*)?$";
            }

            preg_match(
                "#{$pattern}#",
                $path,
                $match,
                PREG_NO_ERROR
            );
            if (!empty($match)) {
                $this->matchedRoute = $route;
                $first = '';
                $last = '';
                if (!$skip_first) {
                    $matched = array_shift($match);
                    $first = $match[0];
                    $match[0] = $matched;
                }
                if (!$skip_last) {
                    $last = array_pop($match);
                }
                $this->matchedParams = [
                    $match,
                    $first,
                    $last
                ];
                break;
            }
        }
        // restore
        restore_error_handler();
        $eventsManager?->dispatch(
            'route.after.looping',
            $this,
            $dispatchedRequest,
            $request
        );

        $eventsManager?->dispatch(
            $this->matchedRoute
                ? 'on.route.match'
                : 'on.route.notFound',
            $this,
            $dispatchedRequest,
            $request
        );

        return $this->matchedRoute;
    }

    /**
     * @return ResponseInterface|null
     */
    public function getResponse(): ?ResponseInterface
    {
        return $this->response;
    }

    public function handle(ServerRequestInterface $request) : ResponseInterface
    {
        if ($this->response) {
            return $this->response;
        }

        $route = $this->dispatch($request);
        if (!$route) {
            $this->response = $this->getResponseFactory()->createResponse(404);
            throw new RouteNotFoundException(
                $this,
                $this->dispatchedRequest??$request
            );
        }

        try {
            $callback = $route->getCallback();
            if (is_array($callback)
                && isset($callback[0])
                && is_subclass_of($callback[0], Controller::class)
            ) {
                if (is_string($callback[0])) {
                    $callback[0] = new $callback[0]($this);
                    $route->setController($callback[0], $callback[1]);
                }
                $this->dispatchedController = $callback[0];
            } else {
                $callback = [
                    CallableController::attach($this, $callback),
                    'route'
                ];
            }

            $theResponse = $callback[0]->dispatch($callback[1], ...$this->getMatchedParams());

            $response = $this
                ->getEventsManager()
                ?->dispatch(
                    'router.response',
                    $theResponse,
                    $this
                );
            if ($response instanceof ResponseInterface) {
                $theResponse = $response;
            }
            $this->response = $theResponse;
            return $this->response;
        } catch (RouteNotFoundException $e) {
            throw $e;
        } catch (Throwable $e) {
            throw new RouteErrorException(
                $this,
                $route,
                $this->dispatchedRequest??$request,
                $e,
                $e->getMessage(),
                $e->getCode()
            );
        }
    }

    /**
     * @return ?Controller
     */
    public function getDispatchedController(): ?Controller
    {
        return $this->dispatchedController;
    }
}
