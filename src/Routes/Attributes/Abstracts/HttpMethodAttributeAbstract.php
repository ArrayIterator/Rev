<?php
declare(strict_types=1);

namespace ArrayIterator\Rev\Source\Routes\Attributes\Abstracts;

use ArrayIterator\Rev\Source\Routes\Attributes\Interfaces\HttpMethodAttributeInterface;
use ArrayIterator\Rev\Source\Routes\Route;

abstract readonly class HttpMethodAttributeAbstract implements HttpMethodAttributeInterface
{
    public array $methods;

    public function __construct(
        public string $pattern,
        public int $priority = Route::DEFAULT_PRIORITY,
        public ?string $name = null,
        public ?string $hostName = null
    ) {
        $method = strtoupper(
            ltrim(strrchr($this::class, '\\'), '\\')
        );
        $method = $method === 'ANY' ? '*' : $method;
        $this->methods = [$method];
    }

    /**
     * @return array<string>
     */
    public function getMethods(): array
    {
        return $this->methods;
    }

    /**
     * @return string
     */
    public function getPattern(): string
    {
        return $this->pattern;
    }

    /**
     * @return int
     */
    public function getPriority(): int
    {
        return $this->priority;
    }

    /**
     * @return string|null
     */
    public function getName(): ?string
    {
        return $this->name;
    }

    /**
     * @return string|null
     */
    public function getHostName(): ?string
    {
        return $this->hostName;
    }
}
