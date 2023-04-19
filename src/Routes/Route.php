<?php
declare(strict_types=1);

namespace ArrayIterator\Rev\Source\Routes;

use InvalidArgumentException;
use ReflectionClass;
use Throwable;

class Route
{
    const DEFAULT_PRIORITY = 10;

    const PLACEHOLDERS = [
        '?:id:' => '(?:.+)?',
        '?:num:' => '(?:\d+)?',
        '?:any:' => '(?:.*)?',
        '?:hex:' => '(?:[0-9A-Fa-f]+)?',
        '?:lower_hex:' => '(?:[0-9a-f]+)?',
        '?:upper_hex:' => '(?:[0-9A-F]+)?',
        '?:alpha:' => '(?:[A-Za-z]+)?',
        '?:lower_alpha:' => '(?:[a-z]+)?',
        '?:upper_alpha:' => '(?:[A-Z]+)?',
        '?:uuid:'    => '(?:(?i)[0-9A-F]{8}-[0-9A-F]{4}-[345][0-9A-F]{3}-[89AB][0-9A-F]{3}-[0-9A-F]{12})?',
        '?:uuid_v3:' => '(?:(?i)[0-9A-F]{8}-[0-9A-F]{4}-3[0-9A-F]{3}-[89AB][0-9A-F]{3}-[0-9A-F]{12})?',
        '?:uuid_v4:' => '(?:(?i)[0-9A-F]{8}-[0-9A-F]{4}-4[0-9A-F]{3}-[89AB][0-9A-F]{3}-[0-9A-F]{12})?',
        '?:uuid_v5:' => '(?:(?i)[0-9A-F]{8}-[0-9A-F]{4}-5[0-9A-F]{3}-[89AB][0-9A-F]{3}-[0-9A-F]{12})?',
        '?:slug:'    => '(?:[a-z0-9]+(?:(?:[0-9a-z_\-]+)?[a-z0-9]+)?)?',
        ':id:'  => '(?:.+)',
        ':num:' => '(?:\d+)',
        ':any:' => '(?:.*)',
        ':hex:' => '(?:[0-9A-Fa-f]+)',
        ':lower_hex:' => '(?:[0-9a-f]+)',
        ':upper_hex:' => '(?:[0-9A-F]+)',
        ':alpha:' => '(?:[A-Za-z]+)',
        ':lower_alpha:' => '(?:[a-z]+)',
        ':upper_alpha:' => '(?:[A-Z]+)',
        ':uuid:'    => '(?:(?i)[0-9A-F]{8}-[0-9A-F]{4}-[345][0-9A-F]{3}-[89AB][0-9A-F]{3}-[0-9A-F]{12})',
        ':uuid_v3:' => '(?:(?i)[0-9A-F]{8}-[0-9A-F]{4}-3[0-9A-F]{3}-[89AB][0-9A-F]{3}-[0-9A-F]{12})',
        ':uuid_v4:' => '(?:(?i)[0-9A-F]{8}-[0-9A-F]{4}-4[0-9A-F]{3}-[89AB][0-9A-F]{3}-[0-9A-F]{12})',
        ':uuid_v5:' => '(?:(?i)[0-9A-F]{8}-[0-9A-F]{4}-5[0-9A-F]{3}-[89AB][0-9A-F]{3}-[0-9A-F]{12})',
        ':slug:'    => '(?:[a-z0-9]+(?:(?:[0-9a-z_\-]+)?[a-z0-9]+)?)',
    ];

    protected string $pattern;

    protected string $compiledPattern;

    protected array $methods = [];
    protected int $priority;

    /**
     * @var callable|array
     */
    protected $callback;

    /**
     * @throws InvalidArgumentException
     */
    public function __construct(
        array|string $methods,
        string $pattern,
        callable|array $controller,
        ?int $priority = self::DEFAULT_PRIORITY,
        protected ?string $name = null,
        protected ?string $hostName = null
    ) {
        $this->priority = $priority??self::DEFAULT_PRIORITY;
        $this->setRoutePattern($pattern);
        $methods = empty($methods) ? ['*'] : $methods;
        $this->methods = $this->filterMethods($methods);
        if (is_callable($controller)) {
            $this->setHandler($controller);
        } else {
            $controller = array_values($controller);
            if (count($controller) < 2) {
                throw new InvalidArgumentException(
                    sprintf(
                        'Argument controller must be contain class %s and method',
                        Controller::class
                    )
                );
            }
            $this->setController($controller[0], $controller[1]);
        }
    }

    /**
     * @param string $pattern
     * @return string
     */
    protected function setRoutePattern(string $pattern): string
    {
        $this->pattern = $pattern;
        $placeholder = static::PLACEHOLDERS;
        if (!is_array($placeholder)) {
            $placeholder = self::PLACEHOLDERS;
        }
        $pattern = str_replace(
            array_keys($placeholder),
            array_values($placeholder),
            $pattern
        );
        $this->compiledPattern = $pattern;
        return $pattern;
    }

    /**
     * @return string
     */
    public function getPattern(): string
    {
        return $this->pattern;
    }

    /**
     * @return string
     */
    public function getCompiledPattern(): string
    {
        return $this->compiledPattern;
    }

    /**
     * @return string|null
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * @param string|null $name
     * @return $this
     */
    public function setName(?string $name): static
    {
        $this->name = $name;
        return $this;
    }

    /**
     * @return int
     */
    public function getPriority(): int
    {
        return $this->priority;
    }

    /**
     * @param int $priority
     * @return Route
     */
    public function setPriority(int $priority): static
    {
        $this->priority = $priority;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getHostName(): ?string
    {
        return $this->hostName;
    }

    /**
     * @param string|null $hostName
     */
    public function setHostName(?string $hostName): static
    {
        $this->hostName = $hostName;
        return $this;
    }

    public function setHandler(callable $callback): static
    {
        $this->callback = $callback;
        return $this;
    }

    /**
     * @return array
     */
    public function getMethods(): array
    {
        return $this->methods;
    }

    /**
     * @param array $methods
     * @return Route
     */
    public function setMethods(array $methods): static
    {
        $methods = $this->filterMethods($methods);
        if (!empty($methods)) {
            $this->methods = $methods;
        }
        return $this;
    }

    /**
     * @return array{0:Controller, 1:string}|callable
     */
    public function getCallback(): callable|array
    {
        return $this->callback;
    }

    /**
     * @throws InvalidArgumentException
     */
    public function setController(
        string|Controller $controller,
        string $method
    ): static {
        try {
            $ref = new ReflectionClass($controller);
            $refMethod = $ref->getMethod($method);
        } catch (Throwable $e) {
            throw new InvalidArgumentException(
                $e->getMessage()
            );
        }
        if (!$ref->isSubclassOf(Controller::class)) {
            throw new InvalidArgumentException(
                sprintf(
                    'Controller must be subclass of %s',
                    Controller::class
                )
            );
        }

        $controllerName = is_object($controller) ? $controller : $ref->getName();
        $this->callback = [
            $controllerName,
            $refMethod->getName()
        ];
        return $this;
    }

    public function containMethod(string $method): bool
    {
        // always true
        if (isset($this->methods['*'])) {
            return true;
        }
        if ($method === '*') {
            return false;
        }
        $method = trim(strtoupper($method));
        return isset($this->methods[$method]);
    }

    public static function filterMethods(string|array $methods): array
    {
        if ($methods === '*') {
            return ['*'];
        }
        $methods = is_string($methods) ? [$methods] : $methods;
        $httpMethods = [];
        foreach ($methods as $method) {
            if (!is_string($method)) {
                continue;
            }
            $method = trim($method);
            if ($method === '') {
                continue;
            }
            if ($method === '*') {
                return ['*' => true];
            }
            $httpMethods[$method] = true;
        }
        return $httpMethods;
    }
}
